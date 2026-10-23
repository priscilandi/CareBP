<?php
declare(strict_types=1);
// Sessions are required for admin access and form CSRF protection.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict', 'use_strict_mode' => true]);
}
function admin_password(): string {
    $env = getenv('ADMIN_PASSWORD');
    if ($env !== false) return (string)$env;
    $file = dirname(__DIR__, 2) . '/.env';
    if (!is_file($file)) return '';
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#')) continue;
        if (preg_match('/^ADMIN_PASSWORD\s*=\s*(.*)$/', $line, $m)) {
            $value = trim($m[1]);
            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) return substr($value, 1, -1);
            return $value;
        }
    }
    return '';
}
function require_admin(): void {
    if (admin_password() === '') { http_response_code(503); exit('Admin access is disabled. Configure ADMIN_PASSWORD in .env.'); }
    if (empty($_SESSION['carebp_admin'])) { header('Location: /admin/login.php'); exit; }
}
