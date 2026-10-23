<?php
/*
 * Attribution: Facility CRUD page adapted from Kendra's original index.php.
 * Kendra's source provided the add/edit form, edit-record loading, location
 * listing, and Edit/Remove action links. This CareBP integration separates
 * write actions into add.php, edit.php, and remove.php, and extends the form
 * to match database/schema.sql (location_id, type_id, and other columns).
 * Database access and security checks are integrated with the project backend.
 */
require_once __DIR__ . '/admin_helpers.php';
$types = $pdo->query('SELECT type_id, type_name FROM location_types ORDER BY type_name')->fetchAll();
$locations = $pdo->query('SELECT l.*, t.type_name FROM healthcare_locations l JOIN location_types t ON l.type_id = t.type_id ORDER BY l.name, l.location_id')->fetchAll();
$editing = null;
if (isset($_GET['edit_id'])) {
    $id = valid_id($_GET['edit_id']);
    $stmt = $pdo->prepare('SELECT * FROM healthcare_locations WHERE location_id = ?');
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
    if (!$editing) { http_response_code(404); exit('Location not found'); }
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
function value_for(?array $editing, string $key): string { return h($editing[$key] ?? ''); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>CareBP Admin — Locations</title>
<style>
:root{font-family:system-ui,Arial,sans-serif;color:#17263a;background:#f4f7fa}*{box-sizing:border-box}body{margin:0}.top{background:#183852;color:#fff;padding:22px max(18px,calc((100vw - 1160px)/2))}h1{margin:0;font-size:26px}.top p{margin:6px 0 0;color:#d7e2ec}.wrap{max-width:1160px;margin:28px auto;padding:0 18px}.panel{background:#fff;border:1px solid #dce4eb;border-radius:12px;padding:22px;margin-bottom:22px;box-shadow:0 2px 8px #1838520a}h2{margin:0 0 18px;font-size:21px}.fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(215px,1fr));gap:15px}label{font-weight:600;font-size:13px;display:flex;flex-direction:column;gap:6px}input,select{font:inherit;border:1px solid #bfcbd5;border-radius:7px;padding:10px;width:100%;background:white}input:focus,select:focus{outline:2px solid #78a9d1}.checks{display:flex;flex-wrap:wrap;gap:24px;margin:18px 0}.checks label{display:flex;flex-direction:row;align-items:center;font-size:14px}.checks input{width:auto}.actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.btn{display:inline-block;border:0;background:#12649a;color:#fff;padding:10px 15px;border-radius:7px;font:inherit;font-size:14px;cursor:pointer;text-decoration:none}.btn:hover{filter:brightness(.92)}.secondary{background:#64748b}.danger{background:#b32939}.notice{background:#e7f6ef;border:1px solid #b0dcc7;border-radius:8px;padding:12px;margin-bottom:20px}.table-scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:14px}td,th{text-align:left;padding:12px 10px;border-bottom:1px solid #e7edf2;vertical-align:top}th{background:#edf3f7;white-space:nowrap}.small{padding:7px 10px;font-size:12px}.row-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.row-actions form{margin:0}.muted{color:#64748b}.count{font-size:14px;color:#64748b;font-weight:400}
</style>
</head><body>
<header class="top"><h1>CareBP Admin Portal</h1><p>Healthcare and blood pressure screening locations</p></header>
<main class="wrap"><p><a href="index.php">&larr; Admin dashboard</a> &nbsp; <a href="logout.php">Sign out</a></p>
<?php if ($flash): ?><p class="notice" role="status"><?= h($flash) ?></p><?php endif; ?>
<section class="panel" id="location-form">
<h2><?= $editing ? 'Edit location #' . h($editing['location_id']) : 'Add a healthcare location' ?></h2>
<form action="<?= $editing ? 'edit.php' : 'add.php' ?>" method="post">
<input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
<?php if ($editing): ?><input type="hidden" name="location_id" value="<?= h($editing['location_id']) ?>"><?php endif; ?>
<div class="fields">
<label>Location name *<input name="name" required maxlength="255" value="<?= value_for($editing,'name') ?>"></label>
<label>Location type *<select name="type_id" required><option value="">Select type</option><?php foreach ($types as $type): ?><option value="<?= h($type['type_id']) ?>" <?= (string)($editing['type_id'] ?? '') === (string)$type['type_id'] ? 'selected' : '' ?>><?= h($type['type_name']) ?></option><?php endforeach; ?></select></label>
<label>Street address *<input name="address" required maxlength="500" value="<?= value_for($editing,'address') ?>"></label>
<label>City *<input name="city" required maxlength="100" value="<?= value_for($editing,'city') ?>"></label>
<label>State (2 letters) *<input name="state" required maxlength="2" pattern="[A-Za-z]{2}" value="<?= value_for($editing,'state') ?>" placeholder="GA"></label>
<label>ZIP code *<input name="zip_code" required pattern="[0-9]{5}(-[0-9]{4})?" value="<?= value_for($editing,'zip_code') ?>"></label>
<label>Phone<input name="phone" value="<?= value_for($editing,'phone') ?>"></label>
<label>Operating hours<input name="hours" value="<?= value_for($editing,'hours') ?>"></label>
<label>Website (https://...)<input type="url" name="website" value="<?= value_for($editing,'website') ?>"></label>
<label>Latitude<input type="number" step="any" min="-90" max="90" name="latitude" value="<?= value_for($editing,'latitude') ?>"></label>
<label>Longitude<input type="number" step="any" min="-180" max="180" name="longitude" value="<?= value_for($editing,'longitude') ?>"></label>
</div>
<div class="checks">
<label><input type="checkbox" name="offers_bp_screening" <?= (int)($editing['offers_bp_screening'] ?? 1) === 1 ? 'checked' : '' ?>> Offers BP screening</label>
<label><input type="checkbox" name="is_active" <?= (int)($editing['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Active location</label>
</div>
<div class="actions"><button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Add location' ?></button><?php if ($editing): ?><a class="btn secondary" href="facilities.php">Cancel edit</a><?php endif; ?></div>
</form>
</section>
<section class="panel"><h2>Existing locations <span class="count">(<?= count($locations) ?>)</span></h2>
<div class="table-scroll"><table><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Address</th><th>Phone</th><th>BP</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($locations as $location): ?><tr>
<td><?= h($location['location_id']) ?></td>
<td><strong><?= h($location['name']) ?></strong></td>
<td><?= h($location['type_name']) ?></td>
<td><?= h($location['address']) ?><br><span class="muted"><?= h($location['city']) ?>, <?= h($location['state']) ?> <?= h($location['zip_code']) ?></span></td>
<td><?= h($location['phone'] ?? '—') ?></td>
<td><?= (int)$location['offers_bp_screening'] ? 'Yes' : 'No' ?></td>
<td><?= (int)$location['is_active'] ? 'Active' : 'Inactive' ?></td>
<td><div class="row-actions"><a class="btn small" href="facilities.php?edit_id=<?= rawurlencode((string)$location['location_id']) ?>#location-form">Edit</a>
<form method="post" action="remove.php" onsubmit="return confirm('Permanently delete this location?');"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>"><input type="hidden" name="location_id" value="<?= h($location['location_id']) ?>"><button class="btn danger small" type="submit">Remove</button></form></div></td></tr><?php endforeach; ?>
<?php if (!$locations): ?><tr><td colspan="8" class="muted">No healthcare locations found. Add one using the form above.</td></tr><?php endif; ?>
</tbody></table></div></section></main></body></html>
