<?php
/*
 * CareBP admin dashboard: based on the project's existing backend/admin/index.php.
 * This dashboard is not copied from Kendra's CRUD index.php. Kendra's
 * location-management concepts are incorporated in facilities.php instead.
 */
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pages = [
 ['Facilities','View, add, edit and remove screening locations','facilities.php'],
 ['Recommendations','Manage BP guidance (not yet implemented)','recommendations.php'],
 ['Approvals','Review pending changes (not yet implemented)','approvals.php'],
 ['Audit Log','Review admin activity (not yet implemented)','audit-log.php'],
];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CareBP Admin</title><style>body{margin:0;font:16px Arial,sans-serif;background:#f5f7f6;color:#222}header{background:#1c5b54;color:white;padding:24px 30px}main{max-width:1000px;margin:40px auto;padding:0 20px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:20px}.card{background:white;padding:22px;border:1px solid #ddd;border-radius:8px}.card a{color:#1c5b54;font-weight:bold}header a{color:white;float:right}</style></head><body><header><a href="logout.php">Sign out</a><h1>CareBP Admin Dashboard</h1></header><main><h2>Administration</h2><p>Use the dashboard to manage CareBP facility records and other administrative content.</p><div class="grid"><?php foreach ($pages as [$title,$description,$link]): ?><section class="card"><h2><?= htmlspecialchars($title) ?></h2><p><?= htmlspecialchars($description) ?></p><a href="<?= htmlspecialchars($link) ?>">Open</a></section><?php endforeach ?></div></main></body></html>
