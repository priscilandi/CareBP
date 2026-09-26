'use strict';

// Public CareBP experience. Selections remain in this tab; only location-search
// filters and requested education/range IDs are sent to the same-origin API.
const $ = (id) => document.getElementById(id);
const tabButtons = [...document.querySelectorAll('.tab-btn')];
const panels = Object.fromEntries(['home', 'learn', 'findcare', 'about'].map(name => [name, $(`tab-${name}`)]));
var map = L.map("map-container").setView([33.4700,-81.9750], 13);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);
function goToTab(name) {
    if (!Object.hasOwn(panels, name)) return;
    for (const [key, panel] of Object.entries(panels)) panel.hidden = key !== name;
    for (const button of tabButtons) {
        if (button.dataset.tab === name) button.setAttribute('aria-current', 'page');
        else button.removeAttribute('aria-current');
    }
    window.scrollTo({top: 0, behavior: 'instant'});
}

tabButtons.forEach(button => button.addEventListener('click', () => goToTab(button.dataset.tab)));
document.addEventListener('click', event => {
    const link = event.target.closest('[data-goto]');
    if (link) {
        event.preventDefault();
        goToTab(link.dataset.goto);
    }
});

async function requestJson(path) {
    const response = await fetch(path, {headers: {Accept: 'application/json'}, credentials: 'same-origin'});
    let payload;
    try { payload = await response.json(); }
    catch { throw new Error('The server did not return valid JSON.'); }
    if (!response.ok) throw new Error(payload?.error?.message || `Request failed (${response.status}).`);
    return payload;
}

function setPressed(buttons, selected) {
    buttons.forEach(button => button.setAttribute('aria-pressed', String(button === selected)));
}

// -------- Home: the uploaded yes/no -> screening -> range -> timing flow --------
const readingButtons = [...document.querySelectorAll('[data-hasreading]')];
const machineButtons = [...document.querySelectorAll('[data-hasmachine]')];
const rangeButtons = [...document.querySelectorAll('[data-bprange]')];
const timeButtons = [...document.querySelectorAll('[data-bptime]')];
const recPanel = $('range-recommendations');
let selectedRange = '';
let selectedTime = '';
let selectionVersion = 0;
let bpCategories = null;

const categoryPromise = requestJson('/api/categories.php')
    .then(payload => {
        bpCategories = payload.data;
        return bpCategories;
    })
    .catch(() => null);

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
    normal: 'This is the normal adult range. A single reading is a snapshot; keep a record of readings over time.',
    elevated: 'This is the elevated adult range. Discuss repeated elevated readings and heart-healthy habits with a healthcare professional.',
    high: 'This matches the Stage 1 adult range. A clinician can assess repeated readings and your other health factors.',
    veryhigh: 'This matches at least the Stage 2 adult range. Arrange clinical follow-up for repeatedly high readings. A reading above 180 systolic or 120 diastolic needs immediate attention if it remains high on repeat; call 911 with emergency symptoms.',
};
const ahaReadings = 'https://www.heart.org/en/health-topics/high-blood-pressure/understanding-blood-pressure-readings';

function currentCategoryId() {
    const namePart = {normal: 'normal', elevated: 'elevated', high: 'stage 1', veryhigh: 'stage 2'}[selectedRange];
    const item = bpCategories?.find(row => row.category_name.toLowerCase().includes(namePart));
    return item?.category_id;
}

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

async function populateRecommendationPanel() {
    if (!selectedRange || !selectedTime) return;
    const thisSelection = ++selectionVersion;
    recPanel.hidden = false;
    $('NextSteps').hidden = false;
    recPanel.replaceChildren();
    putText(recPanel, 'h3', 'Your selected range');
    putText(recPanel, 'p', rangeInfo[selectedRange]);
    if (selectedTime !== 'within3months') {
        putText(recPanel, 'p', 'This reading may no longer reflect your current blood pressure. Consider checking it again and discuss any concerns with a healthcare professional.');
    }
    putLink(recPanel, 'Read the American Heart Association BP categories', ahaReadings);
    putText(recPanel, 'p', 'Checking one box here is not a diagnosis. Pregnancy uses different thresholds.', 'help-text');
    const loading = putText(recPanel, 'p', 'Checking for additional published guidance…', 'help-text');
    const categories = await categoryPromise;
    if (thisSelection !== selectionVersion) return;
    const categoryId = categories ? currentCategoryId() : null;
    if (!categoryId) {
        loading.textContent = 'Additional database guidance is unavailable; the information above is general education.';
        return;
    }
    try {
        const payload = await requestJson(`/api/recommendations.php?category_id=${categoryId}`);
        if (thisSelection !== selectionVersion) return;
        const published = payload.data.filter(row => !row.is_demo);
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
            putText(recPanel, 'p', 'This database currently contains demonstration guidance only; it is not shown as medical advice.', 'help-text');
        }
    } catch {
        if (thisSelection === selectionVersion) loading.textContent = 'Additional database guidance is temporarily unavailable.';
    }
}
rangeButtons.forEach(button => button.addEventListener('click', () => {
    selectedRange = button.dataset.bprange;
    selectedTime = '';
    selectionVersion++;
    setPressed(rangeButtons, button);
    timeButtons.forEach(option => option.setAttribute('aria-pressed', 'false'));
    $('TSLS').hidden = false;
    $('NextSteps').hidden = true;
    recPanel.hidden = true;
    recPanel.replaceChildren();
}));
timeButtons.forEach(button => button.addEventListener('click', () => {
    selectedTime = button.dataset.bptime;
    setPressed(timeButtons, button);
    void populateRecommendationPanel();
}));

// -------- Learn: static, sourced backend articles (no education table in upload) --------
const topicButtons = [...document.querySelectorAll('.view-btn')];
const topicViews = Object.fromEntries([
    'numbers-mean', 'risk', 'check-home', 'pregnancy', 'midlife-and-menopause', 'talk-to-provider', 'managing-bp', 'blood-pressure'
].map(name => [name, $(`topic-${name}`)]));
const learnGrid = document.querySelector('.learn-grid');
const loadedTopics = new Set();
let topicViewVersion = 0;

async function goToTopic(name) {
    if (!Object.hasOwn(topicViews, name)) return;
    const version = ++topicViewVersion;
    learnGrid.hidden = true;
    for (const [key, panel] of Object.entries(topicViews)) panel.hidden = key !== name;
    window.scrollTo({top: 0, behavior: 'instant'});
    topicViews[name].querySelector('h2').focus();
    if (loadedTopics.has(name)) return;
    const body = topicViews[name].querySelector('.topic-body');
    body.replaceChildren(putText(document.createDocumentFragment(), 'p', 'Loading topic…'));
    try {
        const {data} = await requestJson(`/api/education.php?topic=${encodeURIComponent(name)}`);
        if (version !== topicViewVersion) return;
        body.replaceChildren();
        for (const paragraph of data.paragraphs) putText(body, 'p', paragraph);
        putLink(body, `Source: ${data.source_label}`, data.source_url);
        loadedTopics.add(name);
    } catch {
        if (version !== topicViewVersion) return;
        const button = topicButtons.find(item => item.dataset.topic === name);
        body.replaceChildren();
        putText(body, 'p', button?.querySelector('span')?.textContent.trim() || 'This topic is unavailable right now.');
        putText(body, 'p', 'The full education article could not be loaded.', 'help-text');
    }
}
topicButtons.forEach(button => button.addEventListener('click', () => { void goToTopic(button.dataset.topic); }));
document.querySelectorAll('.back-btn').forEach(button => button.addEventListener('click', () => {
    topicViewVersion++;
    Object.values(topicViews).forEach(panel => { panel.hidden = true; });
    learnGrid.hidden = false;
    topicButtons[0]?.focus();
    window.scrollTo({top: 0, behavior: 'instant'});
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

function showSearchError(message = '') {
    searchError.textContent = message;
    searchError.hidden = !message;
}
function mapSearchUrl(location) {
    const address = [location.name, location.address, location.city, location.state, location.zip_code]
        .filter(Boolean).join(', ');
    return `https://www.openstreetmap.org/search?query=${encodeURIComponent(address)}`;
}
function safeWebsite(value) {
    try {
        const url = new URL(value);
        return ['https:', 'http:'].includes(url.protocol) ? url.href : null;
    } catch { return null; }
}
function renderCard(location) {
    const card = document.createElement('article');
    card.className = 'care-card';
    putText(card, 'p', location.type_name, 'care-type');
    putText(card, 'h3', location.name);
    const address = [location.address, location.city, location.state, location.zip_code].filter(Boolean).join(', ');
    putText(card, 'p', address, 'care-address');
    putText(card, 'p', location.hours ? `Hours: ${location.hours}` : 'Hours not provided — call ahead.', 'care-hours');
    const actions = document.createElement('div');
    actions.className = 'care-actions';
    if (location.phone) {
        const cleanPhone = location.phone.replace(/[^+0-9]/g, '');
        if (cleanPhone) putLink(actions, `Call ${location.phone}`, `tel:${cleanPhone}`);
    }
    const website = safeWebsite(location.website);
    if (website) putLink(actions, 'Website', website);
    putLink(actions, 'View on map', mapSearchUrl(location));
    card.append(actions);
    return card;
}
function renderMap(rows) {
    const mapContainer = $('map-container');
    mapContainer.replaceChildren();
    const withCoordinates = rows.filter(row => Number.isFinite(row.latitude) && Number.isFinite(row.longitude)
        && Math.abs(row.latitude) <= 90 && Math.abs(row.longitude) <= 180);
    if (!withCoordinates.length) {
        putText(mapContainer, 'p', 'A map preview is unavailable for these entries because the supplied database has no coordinates. Use “View on map” on any location card to search its address.');
        return;
    }
    const first = withCoordinates[0];
    const lat = first.latitude;
    const lng = first.longitude;
    const delta = 0.025;
    const params = new URLSearchParams({
        bbox: `${lng - delta},${lat - delta},${lng + delta},${lat + delta}`,
        layer: 'mapnik', marker: `${lat},${lng}`,
    });
    const frame = document.createElement('iframe');
    frame.loading = 'lazy';
    frame.referrerPolicy = 'no-referrer';
    frame.title = `Map preview of ${first.name}`;
    frame.src = `https://www.openstreetmap.org/export/embed.html?${params}`;
    mapContainer.append(frame);
    putText(mapContainer, 'p', `Map preview shows ${first.name}. Other results have individual map links.`, 'help-text');
}
async function loadTypes() {
    try {
        const {data} = await requestJson('/api/location-types.php');
        for (const type of data) {
            if (type.location_count === 0) continue;
            const option = document.createElement('option');
            option.value = String(type.type_id);
            option.textContent = `${type.type_name} (${type.location_count})`;
            typeFilter.append(option);
        }
    } catch { /* An unfiltered ZIP search remains usable if type lookup fails. */ }
}
async function loadLocations(page = 1) {
    const zip = zipInput.value.trim();
    if (zip && !/^\d{5}$/.test(zip)) {
        showSearchError('Enter a valid five-digit ZIP code, or leave it blank to browse all locations.');
        zipInput.focus();
        return;
    }
    showSearchError();
    const version = ++requestVersion;
    currentPage = page;
    searchStatus.textContent = 'Searching locations…';
    list.replaceChildren();
    pagination.hidden = true;
    const params = new URLSearchParams({page: String(page), per_page: '9'});
    if (zip) params.set('zip', zip);
    if (typeFilter.value) params.set('type_id', typeFilter.value);
    if (nameFilter.value.trim()) params.set('q', nameFilter.value.trim());
    try {
        const payload = await requestJson(`/api/facilities.php?${params}`);
        if (version !== requestVersion) return;
        const rows = payload.data;
        const meta = payload.meta;
        searchStatus.textContent = meta.total === 0
            ? 'No listed locations match this search. Try another ZIP code or clear a filter.'
            : `${meta.total} matching location${meta.total === 1 ? '' : 's'}${zip ? ` for ZIP ${zip}` : ''}. Showing page ${meta.page} of ${meta.total_pages}.`;
        rows.forEach(location => list.append(renderCard(location)));
        renderMap(rows);
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
searchForm.addEventListener('submit', event => { event.preventDefault(); void loadLocations(1); });
typeFilter.addEventListener('change', () => { void loadLocations(1); });
nameFilter.addEventListener('change', () => { void loadLocations(1); });
$('care-prev').addEventListener('click', () => { if (currentPage > 1) void loadLocations(currentPage - 1); });
$('care-next').addEventListener('click', () => { void loadLocations(currentPage + 1); });
void loadTypes();
void loadLocations(1);
