<?php
// backend/admin/index.php

// Admin authentication to be added later.
// For now, this page just serves as the admin dashboard.

$adminPages = [
    [
        'title' => 'Facilities',
        'description' => 'View and manage CareBP facility records.',
        'link' => 'facilities.php'
    ],
    [
        'title' => 'Recommendations',
        'description' => 'Manage blood pressure guidance and recommendations.',
        'link' => 'recommendations.php'
    ],
    [
        'title' => 'Approvals',
        'description' => 'Review and approve submitted changes.',
        'link' => 'approvals.php'
    ],
    [
        'title' => 'Audit Log',
        'description' => 'Review administrative actions and changes.',
        'link' => 'audit-log.php'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CareBP Admin</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f7f6;
            color: #222;
        }

        header {
            background: #1c5b54;
            color: white;
            padding: 20px 30px;
        }

        main {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .admin-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .admin-card h2 {
            margin-top: 0;
        }

        .admin-card a {
            display: inline-block;
            margin-top: 10px;
            color: #1c5b54;
            font-weight: bold;
            text-decoration: none;
        }

        .admin-card a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<header>
    <h1>CareBP Admin Dashboard</h1>
</header>

<main>
    <h2>Administration</h2>

    <p>
        Use this dashboard to manage CareBP facilities,
        recommendations, approvals, and administrative records.
    </p>

    <div class="admin-grid">

        <?php foreach ($adminPages as $page): ?>

            <section class="admin-card">

                <h2>
                    <?= htmlspecialchars($page['title']) ?>
                </h2>

                <p>
                    <?= htmlspecialchars($page['description']) ?>
                </p>

                <a href="<?= htmlspecialchars($page['link']) ?>">
                    Open
                </a>

            </section>

        <?php endforeach; ?>

    </div>
</main>

</body>
</html>