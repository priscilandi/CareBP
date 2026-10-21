<?php

declare(strict_types=1);

// Local development router: php -S localhost:8000 -t frontend backend/router.php
// This is deliberately an allowlist; the source tree must not become a public web root.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$routes = [
    '/api/categories.php' => __DIR__ . '/api/categories.php',
    '/api/location-types.php' => __DIR__ . '/api/location-types.php',
    '/api/education.php' => __DIR__ . '/api/education.php',
    '/api/facilities.php' => __DIR__ . '/api/facilities.php',
    '/api/recommendations.php' => __DIR__ . '/api/recommendations.php',
    '/api/health.php' => __DIR__ . '/api/health.php',
];

// Admin pages are explicit allowlisted routes; never expose arbitrary backend files.
$adminPaths = ['index.php','facilities.php','add.php','edit.php','remove.php',
               'recommendations.php','approvals.php','audit-log.php','login.php','logout.php'];
if ($path === '/admin' || $path === '/admin/') {
    header('Location: /admin/index.php'); return true;
}
if (is_string($path) && str_starts_with($path, '/admin/')) {
    $name = substr($path, strlen('/admin/'));
    if (in_array($name, $adminPaths, true)) {
        require __DIR__ . '/admin/' . $name; return true;
    }
}
if (isset($routes[$path])) {
    require $routes[$path];
    return true;
}

// The only public static assets shipped in this phase are these three files.
if (in_array($path, ['/', '/index.html', '/styles.css', '/script.js'], true)) {
    return false;
}

http_response_code(404);
if (str_starts_with((string) $path, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => 'not_found', 'message' => 'API endpoint not found.']]);
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
}
return true;
