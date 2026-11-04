
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

$types = $pdo->query(
    'SELECT type_id, type_name
     FROM location_types
     ORDER BY type_name'
)->fetchAll();

$locations = $pdo->query(
    'SELECT l.*, t.type_name
     FROM healthcare_locations l
     JOIN location_types t ON l.type_id = t.type_id
     ORDER BY l.name, l.location_id'
)->fetchAll();

$editing = null;

if (isset($_GET['edit_id'])) {
    $id = valid_id($_GET['edit_id']);

    $stmt = $pdo->prepare(
        'SELECT * FROM healthcare_locations WHERE location_id = ?'
    );

    $stmt->execute([$id]);
    $editing = $stmt->fetch();

    if (!$editing) {
        http_response_code(404);
        exit('Location not found');
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function value_for(?array $editing, string $key): string
{
    return h($editing[$key] ?? '');
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CareBP | Manage Locations</title>

    <style>
        /* CareBP Theme */

        :root {
            --ink: #17212B;
            --muted: #46515C;
            --ocean: #0B5CAD;
            --ocean-dark: #0A2F52;
            --mint: #E6F0FA;
            --logo: #f5b0a9;
            --cream: #F5F7FA;
            --paper: #FFFFFF;
            --border: #D3DCE6;
            --brick: #8C1D18;
            --brick-tint: #FBE4E1;
            --shadow: 0 2px 14px rgb(23 33 43 / 8%);

            font-family: Inter, -apple-system, BlinkMacSystemFont,
                'Segoe UI', Arial, sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--cream);
            color: var(--ink);
            font-size: 16px;
            line-height: 1.55;
        }

        button,
        input,
        select {
            font: inherit;
        }

        a {
            color: var(--ocean-dark);
            text-underline-offset: 3px;
        }

        /* Header */

        .site-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 14px max(20px, calc((100% - 1040px) / 2));
            background: var(--ocean-dark);
            box-shadow: 0 2px 15px rgb(15 62 44 / 7%);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--mint);
            font-size: 1.3rem;
            font-weight: 800;
        }

        .brand-mark {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            background: var(--logo);
            border-radius: 10px;
        }

        .brand-mark svg {
            width: 22px;
            height: 22px;
        }

        /* Header Navigation */

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .header-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 16px;
            background: rgba(255, 255, 255, 0.12);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .header-link:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        /* Main Content */

        main {
            max-width: 1060px;
            margin: 0 auto;
            padding: 34px 24px 80px;
        }

        .page-heading {
            margin-bottom: 28px;
        }

        .page-heading h1 {
            margin: 0 0 9px;
            color: var(--ink);
            font-size: clamp(1.65rem, 4.5vw, 2.3rem);
            line-height: 1.23;
            letter-spacing: -0.025em;
        }

        .page-heading p {
            max-width: 740px;
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
        }

        /* Content Panels */

        .panel {
            margin-bottom: 22px;
            padding: clamp(18px, 3.5vw, 28px);
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .panel h2 {
            margin: 0 0 22px;
            color: var(--ink);
            font-size: 1.24rem;
            line-height: 1.3;
        }

        /* Form Layout */

        .fields {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(min(100%, 220px), 1fr)
            );
            gap: 18px;
        }

        .fields label {
            display: flex;
            flex-direction: column;
            gap: 8px;
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 650;
        }

        .fields input,
        .fields select {
            width: 100%;
            min-width: 0;
            padding: 13px 15px;
            background: var(--paper);
            color: var(--ink);
            border: 1.5px solid var(--border);
            border-radius: 12px;
        }

        .fields input:hover,
        .fields select:hover {
            border-color: var(--ocean);
        }

        .fields input:focus-visible,
        .fields select:focus-visible {
            outline: 3px solid var(--ocean-dark);
            outline-offset: 3px;
        }

        /* Checkboxes */

        .checks {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 24px;
            margin: 24px 0;
        }

        .checks label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }

        .checks input {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: var(--ocean);
            cursor: pointer;
        }

        /* Buttons */

        .actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 13px 20px;
            background: var(--ocean);
            color: white;
            border: 1.5px solid var(--ocean);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 750;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--ocean-dark);
            border-color: var(--ocean-dark);
        }

        .btn.secondary {
            background: var(--paper);
            color: var(--ocean-dark);
            border-color: var(--border);
        }

        .btn.secondary:hover {
            background: var(--mint);
            border-color: var(--ocean);
        }

        .btn.danger {
            background: var(--brick);
            border-color: var(--brick);
            color: white;
        }

        .btn.danger:hover {
            background: #6D1512;
            border-color: #6D1512;
        }

        .btn.small {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        /* Flash Message */

        .notice {
            margin-bottom: 22px;
            padding: 14px 18px;
            background: var(--mint);
            color: var(--ocean-dark);
            border: 1px solid var(--border);
            border-left: 4px solid var(--ocean);
            border-radius: 12px;
            font-weight: 600;
        }

        /* Locations Table */

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--mint);
            color: var(--ocean-dark);
            font-weight: 750;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #F8FAFC;
        }

        .muted {
            color: var(--muted);
            font-size: 0.88rem;
        }

        .count {
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 400;
        }

        /* Table Status */

        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status.active {
            background: var(--mint);
            color: var(--ocean-dark);
        }

        .status.inactive {
            background: var(--brick-tint);
            color: var(--brick);
        }

        /* Table Actions */

        .row-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .row-actions form {
            margin: 0;
        }

        /* Accessibility */

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 3px solid var(--ocean-dark);
            outline-offset: 3px;
        }

        .header-link:focus-visible {
            outline-color: white;
        }

        /* Mobile */

        @media (max-width: 660px) {
            .site-header {
                padding: 12px 16px;
                flex-wrap: wrap;
            }

            .header-actions {
                gap: 8px;
            }

            .header-link {
                padding: 8px 12px;
                font-size: 0.85rem;
            }

            main {
                padding: 25px 15px 50px;
            }

            .panel {
                padding: 20px 17px;
            }

            .fields {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .checks {
                gap: 14px;
            }

            .actions .btn {
                flex: 1;
            }

            th,
            td {
                padding: 11px 9px;
            }
        }
    </style>
</head>

<body>

    <!-- Website Header -->
    <header class="site-header">

        <div class="brand">

            <span class="brand-mark" aria-hidden="true">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#0A2F52"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>
                    <path d="M3.5 12h4l2-3 3 6 2-3h6"/>
                </svg>
            </span>

            <span>CareBP</span>

        </div>

        <!-- Header Navigation -->
        <nav class="header-actions" aria-label="Admin navigation">

            <a href="index.php" class="header-link">
                Admin dashboard
            </a>

            <a href="logout.php" class="header-link">
                Sign out
            </a>

        </nav>

    </header>

    <!-- Main Content -->
    <main>

        <!-- Page Heading -->
        <div class="page-heading">

            <h1>
                Manage Healthcare Locations
            </h1>

            <p>
                Add, edit, and manage healthcare and blood pressure
                screening locations.
            </p>

        </div>

        <!-- Flash Message -->
        <?php if ($flash): ?>

            <p class="notice" role="status">
                <?= h($flash) ?>
            </p>

        <?php endif; ?>

        <!-- Location Form -->
        <section class="panel" id="location-form">

            <h2>
                <?= $editing
                    ? 'Edit location #' . h($editing['location_id'])
                    : 'Add a healthcare location'
                ?>
            </h2>

            <form
                action="<?= $editing ? 'edit.php' : 'add.php' ?>"
                method="post"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION['csrf_token']) ?>"
                >

                <?php if ($editing): ?>

                    <input
                        type="hidden"
                        name="location_id"
                        value="<?= h($editing['location_id']) ?>"
                    >

                <?php endif; ?>

                <!-- Location Fields -->
                <div class="fields">

                    <label>
                        Location name *

                        <input
                            type="text"
                            name="name"
                            required
                            maxlength="255"
                            value="<?= value_for($editing, 'name') ?>"
                        >
                    </label>

                    <label>
                        Location type *

                        <select name="type_id" required>

                            <option value="">
                                Select type
                            </option>

                            <?php foreach ($types as $type): ?>

                                <option
                                    value="<?= h($type['type_id']) ?>"
                                    <?= (string) ($editing['type_id'] ?? '') === (string) $type['type_id'] ? 'selected' : '' ?>
                                >
                                    <?= h($type['type_name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </label>

                    <label>
                        Street address *

                        <input
                            type="text"
                            name="address"
                            required
                            maxlength="500"
                            value="<?= value_for($editing, 'address') ?>"
                        >
                    </label>

                    <label>
                        City *

                        <input
                            type="text"
                            name="city"
                            required
                            maxlength="100"
                            value="<?= value_for($editing, 'city') ?>"
                        >
                    </label>

                    <label>
                        State (2 letters) *

                        <input
                            type="text"
                            name="state"
                            required
                            maxlength="2"
                            pattern="[A-Za-z]{2}"
                            placeholder="GA"
                            value="<?= value_for($editing, 'state') ?>"
                        >
                    </label>

                    <label>
                        ZIP code *

                        <input
                            type="text"
                            name="zip_code"
                            required
                            pattern="[0-9]{5}(-[0-9]{4})?"
                            value="<?= value_for($editing, 'zip_code') ?>"
                        >
                    </label>

                    <label>
                        Phone

                        <input
                            type="text"
                            name="phone"
                            value="<?= value_for($editing, 'phone') ?>"
                        >
                    </label>

                    <label>
                        Operating hours

                        <input
                            type="text"
                            name="hours"
                            value="<?= value_for($editing, 'hours') ?>"
                        >
                    </label>

                    <label>
                        Website (https://...)

                        <input
                            type="url"
                            name="website"
                            value="<?= value_for($editing, 'website') ?>"
                        >
                    </label>

                    <label>
                        Latitude

                        <input
                            type="number"
                            name="latitude"
                            step="any"
                            min="-90"
                            max="90"
                            value="<?= value_for($editing, 'latitude') ?>"
                        >
                    </label>

                    <label>
                        Longitude

                        <input
                            type="number"
                            name="longitude"
                            step="any"
                            min="-180"
                            max="180"
                            value="<?= value_for($editing, 'longitude') ?>"
                        >
                    </label>

                </div>

                <!-- Location Options -->
                <div class="checks">

                    <label>
                        <input
                            type="checkbox"
                            name="offers_bp_screening"
                            <?= (int) ($editing['offers_bp_screening'] ?? 1) === 1 ? 'checked' : '' ?>
                        >

                        Offers BP screening
                    </label>

                    <label>
                        <input
                            type="checkbox"
                            name="is_active"
                            <?= (int) ($editing['is_active'] ?? 1) === 1 ? 'checked' : '' ?>
                        >

                        Active location
                    </label>

                </div>

                <!-- Form Actions -->
                <div class="actions">

                    <button class="btn" type="submit">
                        <?= $editing ? 'Save changes' : 'Add location' ?>
                    </button>

                    <?php if ($editing): ?>

                        <a href="facilities.php" class="btn secondary">
                            Cancel edit
                        </a>

                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- Existing Locations -->
        <section class="panel">

            <h2>
                Existing Locations

                <span class="count">
                    (<?= count($locations) ?>)
                </span>
            </h2>

            <div class="table-scroll">

                <table>

                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Address</th>
                            <th scope="col">Phone</th>
                            <th scope="col">BP</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($locations as $location): ?>

                            <tr>

                                <td>
                                    <?= h($location['location_id']) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= h($location['name']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= h($location['type_name']) ?>
                                </td>

                                <td>
                                    <?= h($location['address']) ?>

                                    <br>

                                    <span class="muted">
                                        <?= h($location['city']) ?>,
                                        <?= h($location['state']) ?>
                                        <?= h($location['zip_code']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= h($location['phone'] ?? '—') ?>
                                </td>

                                <td>
                                    <?= (int) $location['offers_bp_screening']
                                        ? 'Yes'
                                        : 'No'
                                    ?>
                                </td>

                                <td>

                                    <?php if ((int) $location['is_active']): ?>

                                        <span class="status active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="row-actions">

                                        <a
                                            class="btn small"
                                            href="facilities.php?edit_id=<?= rawurlencode((string) $location['location_id']) ?>#location-form"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="post"
                                            action="remove.php"
                                            onsubmit="return confirm('Permanently delete this location?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= h($_SESSION['csrf_token']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="location_id"
                                                value="<?= h($location['location_id']) ?>"
                                            >

                                            <button
                                                class="btn danger small"
                                                type="submit"
                                            >
                                                Remove
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (!$locations): ?>

                            <tr>
                                <td colspan="8" class="muted">
                                    No healthcare locations found.
                                    Add one using the form above.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>

</html>
