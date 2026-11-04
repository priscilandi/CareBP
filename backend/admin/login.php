
<?php
require_once __DIR__ . '/../includes/admin_auth.php';

if (admin_password() === '') {
    http_response_code(503);
    exit('Configure ADMIN_PASSWORD in project .env to enable admin login.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals(admin_password(), (string)($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['carebp_admin'] = true;
        header('Location: /admin/index.php');
        exit;
    }

    $error = 'Incorrect password';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CareBP Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
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

            font-family: Inter, -apple-system,
                BlinkMacSystemFont, 'Segoe UI',
                Arial, sans-serif;
        }

        *, *::before, *::after {
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
            background: var(--ocean-dark);
            padding: 18px 24px;
            box-shadow: 0 2px 15px rgb(15 62 44 / 7%);
        }

        .brand {
            max-width: 1040px;
            margin: 0 auto;
            color: var(--mint);
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        /* Login layout */
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 65px 20px;
            min-height: calc(100vh - 75px);
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 36px;
            box-shadow: var(--shadow);
        }

        .login-card h1 {
            margin: 0 0 8px;
            font-size: 1.8rem;
            line-height: 1.3;
            letter-spacing: -0.025em;
            color: var(--ocean-dark);
        }

        .login-subtitle {
            color: var(--muted);
            font-size: 0.95rem;
            margin: 0 0 28px;
        }

        /* Form */
        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            font-weight: 650;
            margin-bottom: 8px;
        }

        .form-group input {
            display: block;
            width: 100%;
            padding: 13px 15px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            background: white;
            color: var(--ink);
            font: inherit;
            transition: border-color 0.2s ease;
        }

        .form-group input:hover {
            border-color: var(--ocean);
        }

        .form-group input:focus-visible {
            outline: 3px solid var(--ocean-dark);
            outline-offset: 3px;
        }

        /* Sign in button */
        .login-button {
            display: block;
            width: 100%;
            padding: 14px 20px;
            background: var(--ocean);
            color: white;
            border: 1.5px solid var(--ocean);
            border-radius: 12px;
            font: inherit;
            font-weight: 750;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .login-button:hover {
            background: var(--ocean-dark);
            border-color: var(--ocean-dark);
        }

        .login-button:focus-visible {
            outline: 3px solid var(--ocean-dark);
            outline-offset: 3px;
        }

        /* Error */
        .error-message {
            background: var(--brick-tint);
            color: var(--brick);
            border-left: 4px solid var(--brick);
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.92rem;
            font-weight: 650;
        }

        /* Disclaimer */
        .login-disclaimer {
            margin: 26px 0 0;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            font-size: 0.82rem;
            line-height: 1.6;
            color: var(--muted);
        }

        /* Mobile */
        @media (max-width: 600px) {
            .site-header {
                padding: 15px 20px;
            }

            .login-container {
                padding: 32px 16px;
                align-items: flex-start;
            }

            .login-card {
                padding: 26px 22px;
            }

            .login-card h1 {
                font-size: 1.55rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .form-group input,
            .login-button {
                transition: none;
            }
        }
    </style>
</head>

<body>

    <header class="site-header">
        <div class="brand">CareBP</div>
    </header>

    <main class="login-container">
        <section class="login-card" aria-labelledby="login-title">

            <h1 id="login-title">Admin Login</h1>

            <p class="login-subtitle">
                Sign in to access the CareBP administration dashboard.
            </p>

            <?php if ($error): ?>
                <p class="error-message" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post">

                <div class="form-group">
                    <label for="password">Admin password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="login-button">
                    Sign in
                </button>

            </form>

            <p class="login-disclaimer">
                For local development only. Add authentication,
                rate limiting, and other security measures
                before deployment.
            </p>

        </section>
    </main>

</body>
</html>
