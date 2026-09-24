#!/usr/bin/env python3
"""Chromium UI smoke test: --mock tests in-memory HTML/JS; --live uses real API+DB.

Dependency (tests only): python3 -m pip install playwright
System Chromium: --chromium-path /usr/bin/chromium
"""
import argparse
import re
from pathlib import Path

from playwright.sync_api import expect, sync_playwright

MOCK_CATEGORIES = [
    {'category_id': i, 'category_name': name, 'display_order': i}
    for i, name in enumerate(('Normal', 'Elevated', 'Stage 1 Hypertension', 'Stage 2 Hypertension'), 1)
]
MOCK_TYPES = [
    {'type_id': 5, 'type_name': 'Library', 'description': '', 'location_count': 25},
    {'type_id': 6, 'type_name': 'Church', 'description': '', 'location_count': 6},
    {'type_id': 7, 'type_name': 'Community Location', 'description': '', 'location_count': 5},
    {'type_id': 8, 'type_name': 'School', 'description': '', 'location_count': 3},
]
# Three actual records from the supplied development seed; mock has NO database.
MOCK_LOCATIONS = [
    {'location_id': 2, 'type_id': 5, 'type_name': 'Library', 'name': 'Reese Library',
     'address': '2500 Walton Way', 'city': 'Augusta', 'state': 'GA', 'zip_code': '30904'},
    {'location_id': 26, 'type_id': 6, 'type_name': 'Church', 'name': 'Good Shepherd Baptist Church',
     'address': '1714 Olive Road', 'city': 'Augusta', 'state': 'GA', 'zip_code': '30904'},
    {'location_id': 32, 'type_id': 7, 'type_name': 'Community Location', 'name': 'The HUB Augusta Locally Grown',
     'address': '631 Chafee Ave Suite 102', 'city': 'Augusta', 'state': 'GA', 'zip_code': '30904'},
]
for row in MOCK_LOCATIONS:
    row.update({'phone': None, 'hours': None, 'website': None,
                'latitude': None, 'longitude': None, 'offers_bp_screening': 1, 'last_verified': None})


def run(base_url, mock, chromium_path):
    with sync_playwright() as browser_api:
        browser = browser_api.chromium.launch(headless=True, executable_path=chromium_path, args=['--no-sandbox'])
        page = browser.new_page(viewport={'width': 1280, 'height': 800})
        errors = []
        page.on('pageerror', lambda exc: errors.append(str(exc)))
        if mock:
            # Some CI sandboxes prohibit Chromium networking entirely. Mount the
            # actual HTML/JS in memory and mock window.fetch before JS starts.
            project = Path(__file__).resolve().parents[1]
            html = (project / 'frontend/index.html').read_text(encoding='utf-8')
            html = re.sub(r'<script\b[^>]*\bsrc=[^>]*></script>', '', html, flags=re.I)
            html = re.sub(r'<link\b[^>]*>', '', html, flags=re.I)
            page.set_content(html, wait_until='domcontentloaded')
            page.evaluate("""payload => {
                window.fetch = async input => {
                    const u = new URL(String(input), 'http://carebp.invalid');
                    const a = u.searchParams;
                    let data;
                    if (u.pathname === '/api/categories.php') data = {data: payload.categories};
                    else if (u.pathname === '/api/location-types.php') data = {data: payload.types};
                    else if (u.pathname === '/api/recommendations.php') {
                        const id = Number(a.get('category_id'));
                        data = {data: id > 0 && id <= 4 ? [{recommendation_id: id,
                            category_id: id, category_name: payload.categories[id-1].category_name,
                            type_id: null, type_name: null, guidance_text: 'Demo guidance, mock only.',
                            care_level: 'Routine', is_demo: true}] : []};
                    } else if (u.pathname === '/api/facilities.php') {
                        let rows = payload.locations;
                        if (a.has('zip')) rows = rows.filter(r => r.zip_code === a.get('zip'));
                        if (a.has('type_id')) rows = rows.filter(r => String(r.type_id) === a.get('type_id'));
                        if (a.has('q')) {
                            const q = a.get('q').toLowerCase();
                            rows = rows.filter(r => [r.name,r.city,r.type_name].some(x => x.toLowerCase().includes(q)));
                        }
                        const total = rows.length;
                        const page = Number(a.get('page') || 1), perPage = Number(a.get('per_page') || 9);
                        data = {data: rows.slice((page-1)*perPage,page*perPage), meta: {
                            total, page, per_page: perPage, total_pages: Math.max(1,Math.ceil(total/perPage))}};
                    } else if (u.pathname === '/api/education.php') {
                        data = {data: {paragraphs:['The top systolic number reflects pressure when the heart beats.'],
                            source_url:'https://www.heart.org/', source_label:'AHA test source'}};
                    } else throw Error('Unknown mocked API route: ' + u.pathname);
                    return {ok:true, status:200, json:async()=> data};
                };
            }""", {'categories': MOCK_CATEGORIES, 'types': MOCK_TYPES, 'locations': MOCK_LOCATIONS})
            page.add_script_tag(content=(project / 'frontend/script.js').read_text(encoding='utf-8'))
        else:
            response = page.goto(base_url.rstrip('/')+'/', wait_until='domcontentloaded')
            assert response.status == 200

        # Find Care: exercise fetch, card rendering, filtering and client-side validation.
        page.locator('[data-tab="findcare"]').click()
        if not mock:
            expect(page.locator('#care-search-status')).to_contain_text('39 matching locations')
            expect(page.locator('.care-card')).to_have_count(9)
        else:
            expect(page.locator('.care-card')).to_have_count(3)
        page.locator('#zip-input').fill('30904')
        page.locator('#care-search-form button[type="submit"]').click()
        expect(page.locator('.care-card')).to_have_count(3)
        for name in ('Reese Library', 'Good Shepherd Baptist Church', 'The HUB Augusta Locally Grown'):
            expect(page.locator('#care-list')).to_contain_text(name)
        page.locator('#care-type-filter').select_option('6')
        expect(page.locator('.care-card')).to_have_count(1)
        expect(page.locator('#care-list')).to_contain_text('Good Shepherd Baptist Church')
        page.locator('#care-type-filter').select_option('')
        page.locator('#care-name-filter').fill('Reese')
        page.locator('#care-search-form button[type="submit"]').click()
        expect(page.locator('.care-card')).to_have_count(1)
        expect(page.locator('#care-list')).to_contain_text('Reese Library')
        page.locator('#zip-input').fill('1234')
        page.locator('#care-search-form button[type="submit"]').click()
        expect(page.locator('#care-search-error')).to_contain_text('five-digit ZIP')

        # Learn: real public education endpoint can serve without MySQL.
        page.locator('[data-tab="learn"]').click()
        page.locator('[data-topic="numbers-mean"]').click()
        expect(page.locator('#topic-numbers-mean .topic-body')).to_contain_text('systolic')

        # Home: frontend maps categories and intentionally hides demo guidance.
        page.locator('[data-tab="home"]').click()
        page.locator('[data-hasreading="yes"]').click()
        page.locator('[data-bprange="normal"]').click()
        page.locator('[data-bptime="within3months"]').click()
        expect(page.locator('#range-recommendations')).to_contain_text('demonstration guidance only')
        assert not errors, 'JavaScript page errors: ' + '; '.join(errors)
        print('PASS: Chromium UI Home, Learn, Find Care, ZIP validation, filter, and demo suppression '
              + ('(mocked API only; NOT database-tested)' if mock else '(live PHP + MySQL)'))
        browser.close()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument('--mock', action='store_true', help='Intercept API with three seeded sample records; no DB required')
    mode.add_argument('--live', action='store_true', help='Connect to live PHP server with the full 39-row MySQL seed')
    parser.add_argument('--base-url', default='http://127.0.0.1:8000')
    parser.add_argument('--chromium-path', default='/usr/bin/chromium')
    args = parser.parse_args()
    run(args.base_url, args.mock, args.chromium_path)
