<?php
require_once __DIR__ . '/../includes/admin_auth.php';
if (admin_password() === '') { http_response_code(503); exit('Configure ADMIN_PASSWORD in project .env to enable admin login.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals(admin_password(), (string)($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['carebp_admin'] = true;
        header('Location: /admin/index.php'); exit;
    }
    $error = 'Incorrect password';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><title>CareBP Admin Login</title><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="font:16px system-ui;max-width:440px;margin:8vh auto;padding:16px"><h1>CareBP admin sign in</h1><?php if ($error): ?><p role="alert" style="color:#a22"><?= htmlspecialchars($error, ENT_QUOTES) ?></p><?php endif ?><form method="post"><label>Admin password <input type="password" name="password" required autocomplete="current-password"></label><button type="submit">Sign in</button></form><p><small>For local development only. Add production-grade authentication, rate limiting and HTTPS before deployment.</small></p></body></html>
