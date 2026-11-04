
<?php
/*
 * CareBP admin dashboard: based on the project's existing backend/admin/index.php.
 * This dashboard is not copied from Kendra's CRUD index.php. Kendra's
 * location-management concepts are incorporated in facilities.php instead.
 */

require_once __DIR__ . '/../includes/admin_auth.php';

require_admin();

$pages = [
    [
        'Facilities',
        'View, add, edit and remove screening locations',
        'facilities.php'
    ],
    [
        'Recommendations',
        'Manage BP guidance (not yet implemented)',
        'recommendations.php'
    ],
    [
        'Approvals',
        'Review pending changes (not yet implemented)',
        'approvals.php'
    ],
    [
        'Audit Log',
        'Review admin activity (not yet implemented)',
        'audit-log.php'
    ],
];
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CareBP | Admin Dashboard</title>

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
            font-weight: 800;
            font-size: 1.3rem;
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

        /* Sign Out Button */

        .sign-out {
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

        .sign-out:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        /* Main Content */

        main {
            max-width: 1060px;
            margin: 0 auto;
            padding: 34px 24px 80px;
        }

        .dashboard-heading {
            margin-bottom: 30px;
        }

        .dashboard-heading h1 {
            margin: 0 0 9px;
            color: var(--ink);
            font-size: clamp(1.65rem, 4.5vw, 2.3rem);
            line-height: 1.23;
            letter-spacing: -0.025em;
        }

        .dashboard-heading p {
            max-width: 740px;
            margin: 0;
            color: var(--muted);
            font-size: 1.05rem;
        }

        /* Administration Section */

        .admin-section h2 {
            margin: 0 0 20px;
            color: var(--ink);
            font-size: 1.24rem;
            line-height: 1.3;
        }

        /* Dashboard Grid */

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(min(100%, 220px), 1fr)
            );
            gap: 20px;
        }

        /* Dashboard Cards */

        .dashboard-card {
            display: flex;
            flex-direction: column;
            padding: 24px;
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .dashboard-card h3 {
            margin: 0 0 10px;
            color: var(--ocean-dark);
            font-size: 1.14rem;
            line-height: 1.3;
        }

        .dashboard-card p {
            flex-grow: 1;
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.55;
        }

        /* Card Buttons */

        .card-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 13px 17px;
            background: var(--ocean);
            color: white;
            border: 1.5px solid var(--ocean);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 750;
            text-decoration: none;
            text-align: center;
        }

        .card-button:hover {
            background: var(--ocean-dark);
            border-color: var(--ocean-dark);
        }

        /* Accessibility */

        a:focus-visible {
            outline: 3px solid var(--ocean-dark);
            outline-offset: 3px;
        }

        .sign-out:focus-visible {
            outline-color: white;
        }

        /* Mobile */

        @media (max-width: 660px) {
            .site-header {
                padding: 12px 16px;
            }

            main {
                padding: 25px 15px 50px;
            }

            .dashboard-heading {
                margin-bottom: 25px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .dashboard-card {
                padding: 20px 17px;
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

        <a href="logout.php" class="sign-out">
            Sign out
        </a>

    </header>

    <!-- Main Dashboard -->
    <main>

        <!-- Page Heading -->
        <div class="dashboard-heading">

            <h1>
                Admin Dashboard
            </h1>

            <p>
                Use the dashboard to manage CareBP facility
                records and other administrative content.
            </p>

        </div>

        <!-- Administration Section -->
        <section class="admin-section" aria-labelledby="admin-title">

            <h2 id="admin-title">
                Administration
            </h2>

            <!-- Dashboard Cards -->
            <div class="dashboard-grid">

                <?php foreach ($pages as [$title, $description, $link]): ?>

                    <section class="dashboard-card">

                        <h3>
                            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <a
                            href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>"
                            class="card-button"
                        >
                            Open
                        </a>

                    </section>

                <?php endforeach; ?>

            </div>

        </section>

    </main>

</body>

</html>
