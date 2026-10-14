<?php
require_once __DIR__ . '/includes/bootstrap.php';

$error = null;
$databaseError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        try {
            $statement = database()->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();
            if ($user !== false && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];
                set_flash('Welcome back, ' . $user['name'] . '!');
                header('Location: ' . (($_GET['return'] ?? '') === 'favorites' ? 'index.php?favorites=1#movies' : 'index.php'));
                exit;
            }
            $error = 'That email and password combination was not recognized.';
        } catch (RuntimeException $exception) {
            $databaseError = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#060b16">
    <title>Sign in — GODIFLEX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="ambient ambient-one"></div><div class="ambient ambient-two"></div>
    <a class="brand auth-brand" href="index.php"><span class="brand-mark">G</span>GODI<span>FLEX</span></a>
    <main class="auth-layout">
        <div class="auth-visual"><span class="auth-orb"></span><span class="auth-visual-kicker">YOUR NEXT STORY STARTS HERE</span><h1>Good to see<br>you <span>again.</span></h1><p>Your next movie night is just around the corner.</p><a href="index.php" class="auth-back">← Back to exploring</a></div>
        <section class="auth-card">
            <div class="auth-card-top"><span class="auth-sparkle">✦</span><span class="auth-label">WELCOME BACK</span></div>
            <h2>Sign in</h2><p class="auth-intro">Pick up where the good stories begin.</p>
            <?php if ($error !== null): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($databaseError !== null): ?><div class="notice notice-error"><?= e($databaseError) ?></div><?php endif; ?>
            <form method="post" action="login.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" placeholder="you@example.com" value="<?= e((string) ($_POST['email'] ?? '')) ?>">
                <div class="password-label"><label for="password">Password</label><a href="register.php">Create an account</a></div>
                <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Your password">
                <button class="button button-primary auth-submit" type="submit">Sign in <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-terms">By continuing, you agree to keep movie night magical.</p>
        </section>
    </main>
    <script src="assets/js/app.js" defer></script>
</body>
</html>
