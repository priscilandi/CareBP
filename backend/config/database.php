<?php

declare(strict_types=1);

/**
 * Load simple KEY=VALUE settings from the project's private .env file.
 * The file is outside frontend/, so it is never served by the development router.
 * Environment variables take precedence over file settings. No Composer dependency.
 */
function readLocalDbSettings(?string $path = null): array
{
    $path ??= dirname(__DIR__, 2) . '/.env';
    if (!is_file($path)) {
        return [];
    }

    $settings = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Unable to read the local database configuration file.');
    }

    $allowed = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        if (!in_array($key, $allowed, true)) {
            continue;
        }
        $value = trim($parts[1]);
        if (strlen($value) >= 2 &&
            (($value[0] === '"' && str_ends_with($value, '"')) ||
             ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        $settings[$key] = $value;
    }
    return $settings;
}

/** Obtain settings from the shell first, then local .env, then defaults. */
function databaseSettings(): array
{
    $fileSettings = readLocalDbSettings();
    $defaults = [
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'carebp',
        'DB_USER' => 'carebp',
        'DB_PASSWORD' => '',
    ];
    $settings = [];
    foreach ($defaults as $key => $default) {
        $environment = getenv($key);
        $settings[$key] = $environment === false ? ($fileSettings[$key] ?? $default) : $environment;
    }
    return $settings;
}

/** Create one local MySQL PDO connection per request. */
function getDatabase(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $settings = databaseSettings();
    $host = $settings['DB_HOST'];
    $port = $settings['DB_PORT'];
    $name = $settings['DB_NAME'];
    $user = $settings['DB_USER'];
    $password = $settings['DB_PASSWORD'];

    if (preg_match('/^[a-zA-Z0-9.:-]+$/D', $host) !== 1) {
        throw new RuntimeException('Invalid DB_HOST setting. Use a host name or IP address.');
    }
    if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
        throw new RuntimeException('Invalid DB_PORT setting.');
    }
    if (preg_match('/^[a-zA-Z0-9_]+$/D', $name) !== 1) {
        throw new RuntimeException('Invalid DB_NAME setting.');
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int) $port, $name);
    $connection = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $connection;
}
