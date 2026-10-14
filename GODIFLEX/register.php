<?php
require_once __DIR__ . '/includes/bootstrap.php';

$error = null;
$databaseError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || strlen($name) > 100) {
        $error = 'Enter your name (up to 100 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Choose a password with at least 8 characters.';
    } else {
        try {
            $statement = database()->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, \'user\')'
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) database()->lastInsertId(),
                'name' => $name,
                'email' => $email,
                'role' => 'user',
            ];
            set_flash('Welcome to GODIFLEX, ' . $name . '. Your movie night starts here!');
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'An account with that email already exists. Try signing in instead.';
            } else {
                throw $exception;
            }
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
    <title>Create your account — GODIFLEX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="ambient ambient-one"></div><div class="ambient ambient-two"></div>
    <a class="brand auth-brand" href="index.php"><span class="brand-mark">G</span>GODI<span>FLEX</span></a>
    <main class="auth-layout">
        <div class="auth-visual register-visual"><span class="auth-orb"></span><span class="auth-visual-kicker">THERE'S A SEAT FOR YOU</span><h1>Make room<br>for <span>wonder.</span></h1><p>New stories, old favorites, and a little movie-night magic.</p><a href="index.php" class="auth-back">← Explore GODIFLEX</a></div>
        <section class="auth-card">
            <div class="auth-card-top"><span class="auth-sparkle">✦</span><span class="auth-label">JOIN THE STORY</span></div>
            <h2>Create account</h2><p class="auth-intro">Your next favorite movie is waiting.</p>
            <?php if ($error !== null): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($databaseError !== null): ?><div class="notice notice-error"><?= e($databaseError) ?></div><?php endif; ?>
            <form method="post" action="register.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="name">Your name</label>
                <input id="name" name="name" type="text" autocomplete="name" required maxlength="100" placeholder="How should we call you?" value="<?= e((string) ($_POST['name'] ?? '')) ?>">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" placeholder="you@example.com" value="<?= e((string) ($_POST['email'] ?? '')) ?>">
                <label for="password">Password <span class="field-hint">8 characters minimum</span></label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Create a password">
                <button class="button button-primary auth-submit" type="submit">Create my account <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-terms">Already part of the story? <a href="login.php">Sign in</a></p>
        </section>
    </main>
    <script src="assets/js/app.js" defer></script>
</body>
</html>
