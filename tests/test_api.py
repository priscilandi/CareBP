#!/usr/bin/env python3
"""CareBP HTTP smoke tests against the built-in server or Apache.

Default mode requires the exact supplied database/seed.sql imported into MySQL.
Use --expect-db-missing ONLY to validate graceful failures without MySQL/PDO.
"""
import argparse
import json
from urllib.error import HTTPError
from urllib.request import Request, urlopen


def fetch(base_url, path, method='GET'):
    try:
        with urlopen(Request(base_url.rstrip('/') + path, method=method), timeout=8) as r:
            status, raw, content_type = r.status, r.read(), r.headers.get('Content-Type', '')
    except HTTPError as e:
        status, raw, content_type = e.code, e.read(), e.headers.get('Content-Type', '')
    if path.startswith('/api/'):
        assert 'application/json' in content_type, (path, status, content_type)
        try:
            return status, json.loads(raw)
        except ValueError as e:
            raise AssertionError(f'{path}: invalid JSON: {raw[:200]!r}') from e
    return status, raw.decode('utf-8')


def check(base, expect_missing, fixtures):
    checks = 0

    def expect(path, status, method='GET'):
        nonlocal checks
        code, data = fetch(base, path, method)
        assert code == status, (path, code, status, data)
        checks += 1
        return data

    html = expect('/', 200)
    assert 'CareBP' in html and 'care-search-form' in html
    script = expect('/script.js', 200)
    assert 'loadLocations' in script
    article = expect('/api/education.php?topic=numbers-mean', 200)
    assert 'paragraphs' in article['data'] and article['data']['source_url'].startswith('https://')
    assert expect('/api/education.php?topic=unknown', 404)['error']['code'] == 'not_found'
    assert expect('/api/health.php', 405, 'POST')['error']['code'] == 'method_not_allowed'
    assert expect('/api/facilities.php?zip=123', 422)['error']['code'] == 'invalid_zip'
    assert expect('/api/recommendations.php', 422)['error']['code'] == 'invalid_field'
    expect('/.env', 404)

    if expect_missing:
        assert expect('/api/health.php', 503)['error']['code'] == 'database_unavailable'
        assert expect('/api/categories.php', 503)['error']['code'] == 'database_unavailable'
        assert expect('/api/facilities.php?zip=30904', 503)['error']['code'] == 'database_unavailable'
        print(f'PASS: {checks} HTTP assertions; database unavailable as expected (NOT a DB integration test).')
        return

    health = expect('/api/health.php', 200)
    assert health['database'] == 'connected'
    categories = expect('/api/categories.php', 200)['data']
    assert [r['category_name'] for r in categories] == [
        'Normal', 'Elevated', 'Stage 1 Hypertension', 'Stage 2 Hypertension'
    ]
    types = expect('/api/location-types.php', 200)['data']
    assert len(types) == 9
    counts = {r['type_name']: r['location_count'] for r in types}
    assert counts['Library'] == 25 and counts['Church'] == 6
    assert counts['Community Location'] == 5 and counts['School'] == 3
    assert sum(counts.values()) == 39

    first_page = expect('/api/facilities.php?per_page=9&page=1', 200)
    assert first_page['meta']['total'] == 39 and len(first_page['data']) == 9
    assert first_page['meta']['total_pages'] == 5
    fifth_page = expect('/api/facilities.php?per_page=9&page=5', 200)
    assert len(fifth_page['data']) == 3
    augusta = expect('/api/facilities.php?zip=30904', 200)
    assert augusta['meta']['total'] == 3
    names = {row['name'] for row in augusta['data']}
    assert names == {'Reese Library', 'Good Shepherd Baptist Church', 'The HUB Augusta Locally Grown'}
    assert expect('/api/facilities.php?zip=30904&q=Reese', 200)['meta']['total'] == 1
    assert expect('/api/facilities.php?zip=30904&type_id=6', 200)['meta']['total'] == 1
    assert expect('/api/facilities.php?state=SC', 200)['meta']['total'] == 2
    assert expect('/api/facilities.php?q=O%27Kelly', 200)['meta']['total'] == 1
    assert expect('/api/facilities.php?q=%25%5F', 200)['meta']['total'] == 0
    assert expect('/api/facilities.php?type_id=x', 422)['error']['code'] == 'invalid_field'
    assert expect('/api/facilities.php?id=999999', 404)['error']['code'] == 'not_found'
    sample_id = augusta['data'][0]['location_id']
    assert expect(f'/api/facilities.php?id={sample_id}', 200)['data']['location_id'] == sample_id

    for category in categories:
        recs = expect(f"/api/recommendations.php?category_id={category['category_id']}", 200)['data']
        assert len(recs) == 1 and recs[0]['is_demo'] is True
    assert expect('/api/recommendations.php?category_id=999', 200)['data'] == []
    if fixtures:
        assert expect('/api/facilities.php?q=%5BTEST%5D', 200)['meta']['total'] == 0
        assert len(expect('/api/recommendations.php?category_id=1', 200)['data']) == 1
    print(f'PASS: {checks} HTTP assertions against seeded MySQL + PHP API + static frontend.')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--base-url', default='http://127.0.0.1:8000')
    parser.add_argument('--expect-db-missing', action='store_true')
    parser.add_argument('--fixtures', action='store_true', help='Also check isolated negative-only test_fixture.sql')
    arguments = parser.parse_args()
    check(arguments.base_url, arguments.expect_db_missing, arguments.fixtures)
