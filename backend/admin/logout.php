<?php
require_once __DIR__ . '/../includes/admin_auth.php';
$_SESSION = [];
session_regenerate_id(true);
header('Location: /admin/login.php'); exit;
