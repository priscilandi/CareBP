'use strict';

// Public CareBP experience. Selections remain in this tab; only location-search
// filters and requested education/range IDs are sent to the same-origin API.
const $ = (id) => document.getElementById(id);
const tabButtons = [...document.querySelectorAll('.tab-btn')];
const panels = Object.fromEntries(['home', 'learn', 'findcare', 'about'].map(name => [name, $(`tab-${name}`)]));


// Function that takes the name of each tab and switches views based on what the user selects
function goToTab(name) {
    // Check just in case a call is made on a tab that doesn't exist
    if (!Object.hasOwn(panels, name)) return;
    for (const [key, panel] of Object.entries(panels)) panel.hidden = key !== name;
    // Updates the tab button to show the user which one is active and removes the highlight from previous tab button
    for (const button of tabButtons) {
        if (button.dataset.tab === name) button.setAttribute('aria-current', 'page');
        else button.removeAttribute('aria-current');
    }
    // Scroll to the top of the page when a new tab is clicked
    window.scrollTo({top: 0, behavior: 'instant'});

    // If the user is on the Find Care tab and there is a map, then it will resize the map to fit the current markers
    if (name === 'findcare' && careMap) {
        careMap.invalidateSize();
        if (careMarkers) {
            careMap.fitBounds(careMarkers.getBounds().pad(0.15));
        }
    }
}


tabButtons.forEach(button => button.addEventListener('click', () => goToTab(button.dataset.tab)));

// Listener for the whole page that will look out for anything with a goto tag given to it
document.addEventListener('click', event => {
    const link = event.target.closest('[data-goto]');
    if (link) {
        event.preventDefault();
        goToTab(link.dataset.goto);
    }
});

// Validates all json content that is recieved from backend
async function requestJson(path) {
    const response = await fetch(path, {headers: {Accept: 'application/json'}, credentials: 'same-origin'});
    let payload;
    // wait for the json to be decoded
    try { payload = await response.json(); }
    // if the json fails to decode then display an error message to the user
    catch { throw new Error('The server did not return valid JSON.'); }
    // Check the HTTP status and if its not ok throw an error to prevent a crash
    if (!response.ok) throw new Error(payload?.error?.message || `Request failed (${response.status}).`);
    return payload;
}

// helper function that sets the pressed attribute for the one that was pressed and removes it from all other buttons if any have it set to true
function setPressed(buttons, selected) {
    buttons.forEach(button => button.setAttribute('aria-pressed', String(button === selected)));
}

// -------- Home: yes/no -> screening -> range -> timing flow ------------------------------------------------
const readingButtons = [...document.querySelectorAll('[data-hasreading]')];
const machineButtons = [...document.querySelectorAll('[data-hasmachine]')];
const rangeButtons = [...document.querySelectorAll('[data-bprange]')];
const recPanel = $('range-recommendations');
let selectedRange = '';
let selectionVersion = 0;
let bpCategories = null;


const categoryPromise = requestJson('/api/categories.php')
    .then(payload => {
        bpCategories = payload.data;
        return bpCategories;
    })
    .catch(() => null);

// Helper function that moves the users view of the homescreen into the "Yes I have reading" state
function enterYesBranch() {
    setPressed(readingButtons, readingButtons.find(button => button.dataset.hasreading === 'yes'));
    $('home-no-panel').hidden = true;
    $('home-yes-panel').hidden = false;
}
readingButtons.forEach(button => button.addEventListener('click', () => {
    if (button.dataset.hasreading === 'yes') enterYesBranch();
    else {
        setPressed(readingButtons, button);
        $('home-yes-panel').hidden = true;
        $('home-no-panel').hidden = false;
    }
}));
machineButtons.forEach(button => button.addEventListener('click', () => {
    setPressed(machineButtons, button);
    $('Screening-Instructions').hidden = button.dataset.hasmachine !== 'yes';
    $('Screening-Locations/Resources').hidden = button.dataset.hasmachine !== 'no';
}));
document.querySelectorAll('[data-hasreading-ql]').forEach(button => button.addEventListener('click', enterYesBranch));

const rangeInfo = {
    normal: 'This is within the normal adult blood pressure range. A single reading is a snapshot; It is best to keep a record of readings over time.',
    elevated: 'This is within the elevated adult blood pressure range. Discuss repeated elevated readings and heart-healthy habits with a healthcare professional.',
    high: 'This is within the Stage 1 hypertension adult blood pressure range. A healthcare professional can assess your readings over time and consider other health factors.',
    veryhigh: 'This is within the Stage 2 hypertension adult blood pressure range. If your readings remain high over a week or longer, contact a healthcare professional for follow-up. If either your systolic pressure is above 180 or your diastolic pressure is above 120, follow the guidance for very high blood pressure below. ',
    hypertensiveCrisis: 'Your blood pressure is severely high. Wait 5 minutes and measure again. If your systolic pressure is still above 180 or your diastolic pressure is still above 120, contact a healthcare professional immediately. If you have chest pain, shortness of breath, weakness, numbness, changes in vision, or difficulty speaking, call 911.',
};
const ahaReadings = 'https://www.heart.org/en/health-topics/high-blood-pressure/understanding-blood-pressure-readings';

// Given the range chosen by the user, find the matching row in the Real DB and return its numeric value
function currentCategoryId() {
    const namePart = {normal: 'normal', elevated: 'elevated', high: 'stage 1', veryhigh: 'stage 2', hypertensiveCrisis: 'hypertensive crisis'}[selectedRange];
    const item = bpCategories?.find(row => row.category_name.toLowerCase().includes(namePart));
    return item?.category_id;
}

// Below are to DOM-building helper functions used throughout the script.js file.
// building elements with .createElement() is much safer and prevents injections of tags or broken markup from special characters
function putText(container, tag, content, className = '') {
    const element = document.createElement(tag);
    if (className) element.className = className;
    element.textContent = content;
    container.append(element);
    return element;
}
function putLink(container, label, href) {
    const link = document.createElement('a');
    link.href = href;
    link.textContent = label;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    container.append(link);
    return link;
}

// Only runs once the user has picked a range. Once they do it immediatly shows them static hardcoded text while it fetches the extra content
async function populateRecommendationPanel() {
    if (!selectedRange) return;
    const thisSelection = ++selectionVersion;
    recPanel.hidden = false;
    $('NextSteps').hidden = false;;
    recPanel.replaceChildren();
    putText(recPanel, 'h3', 'Blood pressure reading range you selected');
    putText(recPanel, 'p', rangeInfo[selectedRange]);

    recPanel.classList.toggle('rec-panel--crisis', selectedRange === 'hypertensiveCrisis');
    recPanel.setAttribute('aria-live', selectedRange === 'hypertensiveCrisis' ? 'assertive' : 'polite');

    if(selectedRange === 'hypertensiveCrisis') {
        putLink(recPanel, 'Call 911', 'tel:911');
    }
    recPanel.scrollIntoView({behavior: 'auto', block: 'start'});
    
    putLink(recPanel, 'Learn about the American Heart Association blood pressure categories', ahaReadings);
    putText(recPanel, 'p', 'This information is for educational purposes only and is not a diagnosis. Blood pressure thresholds during pregnancy are different.', 'help-text');
    const loading = putText(recPanel, 'p', 'Checking for additional published guidance…', 'help-text');
    const categories = await categoryPromise;
    // Checks to see if a range is selected
    if (thisSelection !== selectionVersion) return;
    const categoryId = categories ? currentCategoryId() : null;
    if (!categoryId) {
        // Either the fetch failed, or a match couldn't be made for the range that the user selected
        loading.textContent = 'Additional database guidance is unavailable; the information above is general education.';
        return;
    }
    try {
        // Ask for additional recommendation rows tied to chosen range
        const payload = await requestJson(`/api/recommendations.php?category_id=${categoryId}`);
        // Checks for a stale state
        if (thisSelection !== selectionVersion) return;
        const published = payload.data.filter(row => !row.is_demo);
        // Removes the checking message once the check for stale state is finished
        loading.remove(); 
        if (published.length) {
            putText(recPanel, 'h4', 'Additional published guidance');
            for (const recommendation of published) {
                putText(recPanel, 'p', recommendation.guidance_text);
                if (recommendation.type_name) {
                    putText(recPanel, 'p', `Suggested location type: ${recommendation.type_name}`, 'help-text');
                }
            }
        } else {
            putText(recPanel, 'p', 'This database currently contains demonstration content only. It is not medical advice.', 'help-text');
        }
    } catch {
        if (thisSelection === selectionVersion) loading.textContent = 'Additional database guidance is temporarily unavailable.';
    }
}
rangeButtons.forEach(button => button.addEventListener('click', () => {
    selectedRange = button.dataset.bprange;
    setPressed(rangeButtons, button);
    populateRecommendationPanel();
}));

// -------- Find Care: active screening locations in the uploaded SQL seed --------
const searchForm = $('care-search-form');
const zipInput = $('zip-input');
const typeFilter = $('care-type-filter');
const nameFilter = $('care-name-filter');
const list = $('care-list');
const searchError = $('care-search-error');
const searchStatus = $('care-search-status');
const pagination = $('care-pagination');
let currentPage = 1;
let requestVersion = 0;
let careMap = null;
let careMarkers = null;
// Marker color for each location type; the map pins and the card pills both use it
const TYPE_COLORS = {
    'Church': 'orange',
    'Community Location': 'green',
    'Library': 'purple',
    'School': 'blue',
};

// Helper function to show the error message on a failed search
function showSearchError(message = '') {
    searchError.textContent = message;
    searchError.hidden = !message;
}
// Builds an OpenStreetMap search URL for a location without precise coordinated - This provides a fallback so the user can still see the map
function mapSearchUrl(location) {
    const address = [location.name, location.address, location.city, location.state, location.zip_code]
        .filter(Boolean).join(', ');
    return `https://www.openstreetmap.org/search?query=${encodeURIComponent(address)}`;
}
// Checks that a url is syntactically correct before it turns it into a clickable <a href="...">
function safeWebsite(value) {
    try {
        const url = new URL(value);
        return ['https:', 'http:'].includes(url.protocol) ? url.href : null;
    } catch { return null; }
}

// Build on location "card" DOM element
function renderCard(location) {
    const card = document.createElement('article');
    card.className = 'care-card';
    
    // New marker color system for the cards and the map pins. The color is based on the location type, and if there is no match then it will use a default gray color
    const typeColor = TYPE_COLORS[location.type_name];
    putText(card, 'p', location.type_name, typeColor ? `care-type care-type--${typeColor}` : 'care-type');    putText(card, 'h3', location.name);

    const address = [location.address, location.city, location.state, location.zip_code].filter(Boolean).join(', ');
    putText(card, 'p', address, 'care-address');

    if (Number.isFinite(location.distance_miles)) {
    putText(card, 'p', `${location.distance_miles.toFixed(2)} miles away`, 'care-distance');
    }
    
    putText(card, 'p', location.hours ? `Hours: ${location.hours}` : 'Hours not provided — call ahead.', 'care-hours');
    const actions = document.createElement('div');
    actions.className = 'care-actions';
    if (location.phone) {
        // Cleans up the phone number provided with the care locations information
        const cleanPhone = location.phone.replace(/[^+0-9]/g, '');
        if (cleanPhone) putLink(actions, `Call ${location.phone}`, `tel:${cleanPhone}`);
    }
    const website = safeWebsite(location.website);
    if (website) putLink(actions, 'Website', website);
    putLink(actions, 'View on map', mapSearchUrl(location));
    card.append(actions);
    return card;
}

// Show the embedded OpenStreetMap iframe if there is at least one result with valid coordinates
function renderMap(rows) {
    const mapContainer = $('map-container');

    // Removes any existing map before adding a new one
    if (careMap) {
        careMap.remove();
        careMap = null;
        careMarkers = null;
    }
    mapContainer.replaceChildren();

    // Ethans attempt to get the map working
    const withCoordinates = rows
    .map(row => ({...row, latitude: parseFloat(row.latitude), longitude: parseFloat(row.longitude)}))
    .filter(row => Number.isFinite(row.latitude) && Number.isFinite(row.longitude)
        && Math.abs(row.latitude) <= 90 && Math.abs(row.longitude) <= 180);


    if (!withCoordinates.length) {
        putText(mapContainer, 'p', 'A map preview is unavailable for these entries because the supplied database has no coordinates. Use “View on map” on any location card to search its address.');
        return;
    }

    const mapDiv = document.createElement('div');
    mapDiv.id = 'care-map';
    mapDiv.setAttribute('role', 'img');
    mapDiv.setAttribute('aria-label', `Map showing ${withCoordinates.length} location${withCoordinates.length === 1 ? '' : 's'}`);
    mapContainer.append(mapDiv);

    careMap = L.map('care-map', {scrollWheelZoom: false});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        subdomains: 'abc',
        maxZoom: 19,
    }).addTo(careMap);

    // Ethan's attempt to get the map working
    const markers = withCoordinates.map(row => {
        const popup = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = row.name;
        popup.append(name);
        if (row.address) {
            popup.append(document.createElement('br'), row.address);
        }
         let marker = L.marker([row.latitude, row.longitude]).bindPopup(popup);
        switch(row.type_name)
            {
                case "Church":
                    marker.on('add', function()// Waits for the marker to be added to the map before running
                    {
                        if (marker._icon) 
                        {
                            marker._icon.classList.add("orange");
                        }
                    });
                    break;
                case "Community Location":
                     marker.on('add', function() 
                    {
                        if (marker._icon) 
                        {
                            marker._icon.classList.add("green");
                        }
                    });
                    break;
                case "Library":
                     marker.on('add', function() 
                    {
                        if (marker._icon) 
                        {
                            marker._icon.classList.add("purple");
                        }
                    });
                    break;
                case "School":
                     marker.on('add', function() 
                    {
                        if (marker._icon) 
                        {
                            marker._icon.classList.add("blue");
                        }
                    });
                    break;
        
            }
        return marker;
        });

    careMarkers = L.featureGroup(markers).addTo(careMap);
    careMap.fitBounds(careMarkers.getBounds().pad(0.15));
}

// Temporary: manually-looked-up coordinates until the backend stores them.
const MANUAL_COORDINATES = {
    12: { latitude: 33.4735, longitude: -82.0105 }, // match by facility id
    13: { latitude: 33.4701, longitude: -81.9622 },
    // ...
};

function applyManualCoordinates(rows) {
    return rows.map(row => ({ ...row, ...MANUAL_COORDINATES[row.id] }));
}

// Gets each location types and populates the lsit of locations found in the ZIP code aread
async function loadTypes() {
    try {
        const {data} = await requestJson('/api/location-types.php');
        for (const type of data) {
            // Skips any types that have zero locations attached
            if (type.location_count === 0) continue;
            const option = document.createElement('option');
            option.value = String(type.type_id);
            option.textContent = `${type.type_name} (${type.location_count})`;
            typeFilter.append(option);
        }
    } catch { /* An unfiltered ZIP search remains usable if type lookup fails. */ }
}

// Runs all of the actual Find Care requests
async function loadLocations(page = 1) {
    const zip = zipInput.value.trim();
    // Validates the ZIP is 5-digits and contains no characters
    if (zip && !/^\d{5}$/.test(zip)) {
        showSearchError('Enter a valid five-digit ZIP code, or leave it blank to browse all locations.');
        zipInput.focus();
        return;
    }
    showSearchError();
    // Check for a stale state
    const version = ++requestVersion;
    currentPage = page;
    searchStatus.textContent = 'Searching locations…';
    list.replaceChildren();
    pagination.hidden = true;

    // Builds a query string in order to handle things like spaces and special characters correctly instead of concatenating by hand
    const params = new URLSearchParams({page: String(page), per_page: '9'});
    if (zip) {
        params.set('zip', zip);
        params.set('nearby', '1');
        const radius = document.getElementById('care-radius').value;
        params.set('radius_miles', radius);
    }
    if (typeFilter.value) params.set('type_id', typeFilter.value);
    if (nameFilter.value.trim()) params.set('q', nameFilter.value.trim());

    try {
        const payload = await requestJson(`/api/facilities.php?${params}`);
        // If there is a stale state then we need to terminate the search for the last ZIP provided to begin the new search
        if (version !== requestVersion) return;
        const rows = payload.data;
        const meta = payload.meta;
        // Build a string to describe the results of the search
        searchStatus.textContent = meta.total === 0
            ? 'No listed locations match this search. Try another ZIP code or clear a filter.'
            : `${meta.total} matching location${meta.total === 1 ? '' : 's'}${zip ? ` near ZIP ${zip}` : ''}. Showing page ${meta.page} of ${meta.total_pages}.`;
        rows.forEach(location => list.append(renderCard(location)));
        renderMap(applyManualCoordinates(rows));

        // Only display the pagination controls if there's more than one page of results
        if (meta.total_pages > 1) {
            pagination.hidden = false;
            $('care-page-label').textContent = `Page ${meta.page} of ${meta.total_pages}`;
            $('care-prev').disabled = meta.page <= 1;
            $('care-next').disabled = meta.page >= meta.total_pages;
        }
    } catch (error) {
        if (version !== requestVersion) return;
        searchStatus.textContent = '';
        showSearchError(`Unable to load locations: ${error.message}`);
        $('map-container').replaceChildren();
        putText($('map-container'), 'p', 'Location data is currently unavailable.');
    }
}

// Presents a full-page reload when submitting a new ZIP form
searchForm.addEventListener('submit', event => { event.preventDefault(); void loadLocations(1); });

// Listens for changes in the ZIP search box and the drop down. If there is a change in either the whole page is re-loaded
typeFilter.addEventListener('change', () => { void loadLocations(1); });
nameFilter.addEventListener('change', () => { void loadLocations(1); });
$('care-prev').addEventListener('click', () => { if (currentPage > 1) void loadLocations(currentPage - 1); });
$('care-next').addEventListener('click', () => { void loadLocations(currentPage + 1); });

// These two calls below run immediately when the script loads to insure that users are seeing real data as soon as they
// click to the Find Care tab
void loadTypes();
void loadLocations(1);
