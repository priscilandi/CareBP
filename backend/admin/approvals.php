<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();

// DEMO ONLY: replace the session arrays with database records when the backend is ready.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['carebp_demo_approvals'])) {
    $_SESSION['carebp_demo_approvals'] = [
        101 => ['id' => 101, 'type' => 'Facility update', 'title' => 'Augusta Community Clinic', 'details' => 'Change opening hours to 8:00 AM–5:00 PM', 'submitted_by' => 'Taylor M.', 'submitted_at' => '2026-11-02 10:15', 'status' => 'Pending'],
        102 => ['id' => 102, 'type' => 'New facility', 'title' => 'Downtown Screening Center', 'details' => 'Add a new blood pressure screening location', 'submitted_by' => 'Jordan P.', 'submitted_at' => '2026-11-03 14:30', 'status' => 'Pending'],
        103 => ['id' => 103, 'type' => 'Recommendation', 'title' => 'Blood pressure guidance', 'details' => 'Review an updated recommendation description', 'submitted_by' => 'Casey R.', 'submitted_at' => '2026-11-01 09:00', 'status' => 'Approved'],
        104 => ['id' => 104, 'type' => 'Facility update', 'title' => 'Northside Health Station', 'details' => 'Update address and contact information', 'submitted_by' => 'Sam L.', 'submitted_at' => '2026-11-04 08:20', 'status' => 'Pending'],
    ];
}
if (!isset($_SESSION['carebp_demo_audit'])) {
    $_SESSION['carebp_demo_audit'] = [
        ['time' => '2026-11-04 08:20', 'actor' => 'Sam L.', 'action' => 'Submitted', 'item' => 'Northside Health Station', 'details' => 'Facility update #104 submitted for review'],
        ['time' => '2026-11-03 14:30', 'actor' => 'Jordan P.', 'action' => 'Submitted', 'item' => 'Downtown Screening Center', 'details' => 'New facility #102 submitted for review'],
        ['time' => '2026-11-02 10:15', 'actor' => 'Taylor M.', 'action' => 'Submitted', 'item' => 'Augusta Community Clinic', 'details' => 'Facility update #101 submitted for review'],
        ['time' => '2026-11-01 11:40', 'actor' => 'Demo Admin', 'action' => 'Approved', 'item' => 'Blood pressure guidance', 'details' => 'Recommendation #103 approved'],
    ];
}
if (empty($_SESSION['carebp_demo_csrf'])) {
    $_SESSION['carebp_demo_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $decision = $_POST['decision'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['carebp_demo_csrf'], $token)) {
        $_SESSION['carebp_demo_flash'] = ['error' => true, 'text' => 'Invalid request token. Please try again.'];
    } elseif (!in_array($decision, ['approve', 'reject'], true) || !$id || !isset($_SESSION['carebp_demo_approvals'][$id])) {
        $_SESSION['carebp_demo_flash'] = ['error' => true, 'text' => 'The selected request could not be found.'];
    } elseif ($_SESSION['carebp_demo_approvals'][$id]['status'] !== 'Pending') {
        $_SESSION['carebp_demo_flash'] = ['error' => true, 'text' => 'This request has already been reviewed.'];
    } else {
        $newStatus = $decision === 'approve' ? 'Approved' : 'Rejected';
        $_SESSION['carebp_demo_approvals'][$id]['status'] = $newStatus;
        $item = $_SESSION['carebp_demo_approvals'][$id];
        // Demo actor name; later use the authenticated admin identity from your auth system.
        array_unshift($_SESSION['carebp_demo_audit'], [
            'time' => date('Y-m-d H:i'),
            'actor' => 'Demo Admin',
            'action' => $newStatus,
            'item' => $item['title'],
            'details' => $item['type'] . ' #' . $id . ' ' . strtolower($newStatus),
        ]);
        $_SESSION['carebp_demo_flash'] = ['error' => false, 'text' => 'Request #' . $id . ' marked ' . strtolower($newStatus) . '.'];
    }

    // Redirect after POST so refreshing the page doesn't repeat an action.
    header('Location: approvals.php');
    exit;
}

$flash = $_SESSION['carebp_demo_flash'] ?? null;
unset($_SESSION['carebp_demo_flash']);
$approvals = $_SESSION['carebp_demo_approvals'];
$counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
foreach ($approvals as $request) {
    $counts[$request['status']]++;
}
$statusFilter = $_GET['status'] ?? 'All';
if (!is_string($statusFilter) || !in_array($statusFilter, ['All', 'Pending', 'Approved', 'Rejected'], true)) {
    $statusFilter = 'All';
}
$visible = array_filter($approvals, static fn($request) => $statusFilter === 'All' || $request['status'] === $statusFilter);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CareBP | Approvals</title>
    <link rel="stylesheet" href="admin-pages.css">
</head>
<body>
    <header class="site-header">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#0A2F52" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>
                    <path d="M3.5 12h4l2-3 3 6 2-3h6"/>
                </svg>
            </span>
            <span>CareBP</span>
        </div>
        <a href="logout.php" class="sign-out">Sign out</a>
    </header>

    <main>
        <a href="index.php" class="back-link">&larr; Back to Dashboard</a>
        <div class="page-heading">
            <h1>Approvals</h1>
            <p>Review submitted facility and recommendation changes before they are published.</p>
        </div>

        <?php if ($flash): ?>
            <p class="notice<?= $flash['error'] ? ' error' : '' ?>" role="status"><?= e($flash['text']) ?></p>
        <?php endif; ?>

        <div class="summary-grid" aria-label="Approval request summary">
            <section class="summary-card"><p class="summary-label">Pending</p><p class="summary-value"><?= $counts['Pending'] ?></p></section>
            <section class="summary-card"><p class="summary-label">Approved</p><p class="summary-value"><?= $counts['Approved'] ?></p></section>
            <section class="summary-card"><p class="summary-label">Rejected</p><p class="summary-value"><?= $counts['Rejected'] ?></p></section>
        </div>

        <section class="panel" aria-labelledby="requests-heading">
            <h2 class="panel-title" id="requests-heading">Change Requests</h2>
            <p class="panel-description">Approve or reject pending requests. Decisions are demo-only until approval persistence is implemented.</p>
            <form action="approvals.php" method="get" class="toolbar">
                <div class="field">
                    <label for="status">Filter by status</label>
                    <select id="status" name="status">
                        <?php foreach (['All', 'Pending', 'Approved', 'Rejected'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= $statusFilter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="action-button primary" type="submit">Apply Filter</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th scope="col">Request</th><th scope="col">Submitted by</th><th scope="col">Date</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($visible as $request): ?>
                        <tr>
                            <td><span class="row-title"><?= e($request['title']) ?></span><span class="row-subtitle"><?= e($request['type']) ?> #<?= (int) $request['id'] ?></span><span class="row-subtitle"><?= e($request['details']) ?></span></td>
                            <td><?= e($request['submitted_by']) ?></td>
                            <td><?= e($request['submitted_at']) ?></td>
                            <td><span class="status status-<?= strtolower(e($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                            <td>
                                <?php if ($request['status'] === 'Pending'): ?>
                                    <div class="actions">
                                        <form action="approvals.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['carebp_demo_csrf']) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                            <input type="hidden" name="decision" value="approve">
                                            <button type="submit" class="action-button primary">Approve</button>
                                        </form>
                                        <form action="approvals.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['carebp_demo_csrf']) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                            <input type="hidden" name="decision" value="reject">
                                            <button type="submit" class="action-button secondary">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="row-subtitle">Reviewed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$visible): ?>
                        <tr><td colspan="5"><p class="empty-state">No requests match this filter.</p></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="demo-note">Demo data only: approvals update this PHP session, not the actual facility or recommendation records.</p>
        </section>
    </main>
</body>
</html>
