<?php
require_once __DIR__ . '/includes/bootstrap.php';
try {
    $admin = require_admin();
} catch (RuntimeException $exception) {
    http_response_code(503);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>GODIFLEX setup required</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body class="auth-page">
        <main class="auth-layout">
            <section class="auth-card">
                <a class="brand" href="index.php"><span class="brand-mark">G</span>GODI<span>FLEX</span></a>
                <h2>Dashboard unavailable</h2>
                <div class="notice notice-error"><?= e($exception->getMessage()) ?></div>
                <p class="auth-terms"><a href="index.php">Back to GODIFLEX</a></p>
            </section>
        </main>
    </body>
    </html>
    <?php
    exit;
}
$message = null;
$error = null;
$posters = movie_posters();
$connection = database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_movie') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $poster = (string) ($_POST['poster'] ?? '');
        if ($title === '' || strlen($title) > 150) {
            $error = 'Enter a movie title (up to 150 characters).';
        } elseif (!in_array($poster, $posters, true)) {
            $error = 'Choose an image from the Pics folder.';
        } else {
            try {
                $statement = $connection->prepare('INSERT INTO movies (title, poster) VALUES (:title, :poster)');
                $statement->execute(['title' => $title, 'poster' => $poster]);
                $message = 'Movie added to your collection.';
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That movie or poster is already in your collection.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($action === 'delete_movie') {
        $movieId = filter_var($_POST['movie_id'] ?? null, FILTER_VALIDATE_INT);
        if ($movieId === false || $movieId === null || $movieId < 1) {
            $error = 'Select a valid movie to remove.';
        } else {
            $statement = $connection->prepare('DELETE FROM movies WHERE id = :id');
            $statement->execute(['id' => $movieId]);
            $message = $statement->rowCount() > 0 ? 'Movie removed from your collection.' : 'That movie was already removed.';
        }
    } elseif ($action === 'change_role') {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $role = (string) ($_POST['role'] ?? '');
        if ($userId === false || $userId === null || $userId < 1 || !in_array($role, ['user', 'admin'], true)) {
            $error = 'Select a valid account and role.';
        } elseif ($userId === (int) $admin['id']) {
            $error = 'You cannot change your own administrator role.';
        } else {
            $statement = $connection->prepare('SELECT role FROM users WHERE id = :id');
            $statement->execute(['id' => $userId]);
            $previousRole = $statement->fetchColumn();
            if ($previousRole === false) {
                $error = 'That account could not be found.';
            } elseif ($previousRole === 'admin' && $role === 'user') {
                $adminCount = (int) $connection->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                if ($adminCount <= 1) {
                    $error = 'At least one administrator account must remain.';
                }
            }
            if ($error === null) {
                $statement = $connection->prepare('UPDATE users SET role = :role WHERE id = :id');
                $statement->execute(['role' => $role, 'id' => $userId]);
                $message = 'Account role updated.';
            }
        }
    } else {
        $error = 'That dashboard action is not recognized.';
    }
}

$movies = $connection->query('SELECT id, title, poster FROM movies ORDER BY title')->fetchAll();
$users = $connection->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$flash = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#060b16">
    <title>Admin dashboard — GODIFLEX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
    <div class="ambient ambient-one"></div>
    <header class="site-header dashboard-header">
        <a class="brand" href="index.php"><span class="brand-mark">G</span>GODI<span>FLEX</span></a>
        <nav class="main-nav"><a class="nav-link" href="index.php">← Back to site</a><span class="dashboard-nav-label">ADMIN STUDIO</span></nav>
        <div class="header-actions">
            <span class="admin-chip"><span></span> ADMIN</span>
            <form action="logout.php" method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="button button-outline" type="submit">Log out</button></form>
        </div>
    </header>
    <main class="dashboard-main">
        <section class="dashboard-intro">
            <div><span class="eyebrow"><span class="eyebrow-dot"></span> YOUR CONTROL ROOM</span><h1>Good evening, <span><?= e($admin['name']) ?>.</span></h1><p>Keep the stories flowing. Here's your GODIFLEX at a glance.</p></div>
            <div class="dashboard-date"><?= date('l, F j, Y') ?></div>
        </section>
        <?php if ($flash !== null): ?><div class="notice notice-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
        <?php if ($message !== null): ?><div class="notice notice-success"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error !== null): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
        <section class="stat-grid">
            <article class="stat-card"><span class="stat-icon">▣</span><span class="stat-label">IN YOUR COLLECTION</span><strong><?= count($movies) ?></strong><span class="stat-foot">Movies available to explore</span></article>
            <article class="stat-card"><span class="stat-icon stat-icon-purple">♙</span><span class="stat-label">COMMUNITY MEMBERS</span><strong><?= count($users) ?></strong><span class="stat-foot">Registered GODIFLEX accounts</span></article>
            <article class="stat-card"><span class="stat-icon stat-icon-green">✦</span><span class="stat-label">STUDIO STATUS</span><strong class="status-online">Looking good</strong><span class="stat-foot"><span class="status-dot"></span> Your dashboard is up to date</span></article>
        </section>
        <div class="dashboard-columns">
            <section class="dashboard-panel">
                <div class="panel-heading"><div><span class="panel-kicker">THE COLLECTION</span><h2>Manage movies</h2></div><span class="panel-count"><?= count($movies) ?> TITLES</span></div>
                <form method="post" class="add-movie-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add_movie">
                    <label class="sr-only" for="movie-title">Movie title</label><input id="movie-title" name="title" maxlength="150" required placeholder="Add a movie title">
                    <label class="sr-only" for="movie-poster">Choose poster</label>
                    <select id="movie-poster" name="poster" required><option value="">Choose a poster from Pics</option><?php foreach ($posters as $poster): ?><option value="<?= e($poster) ?>"><?= e(pathinfo($poster, PATHINFO_FILENAME)) ?></option><?php endforeach; ?></select>
                    <button class="button button-primary" type="submit">Add movie <span aria-hidden="true">+</span></button>
                </form>
                <div class="table-wrap"><table class="admin-table"><thead><tr><th>MOVIE</th><th>POSTER</th><th></th></tr></thead><tbody>
                    <?php foreach ($movies as $movie): ?><tr><td><div class="table-movie"><img src="Pics/<?= rawurlencode($movie['poster']) ?>" alt=""><strong><?= e($movie['title']) ?></strong></div></td><td class="muted-cell"><?= e($movie['poster']) ?></td><td class="table-action"><form method="post" onsubmit="return confirm('Remove this movie from the collection?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_movie"><input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>"><button class="remove-button" type="submit" aria-label="Remove <?= e($movie['title']) ?>">Remove</button></form></td></tr><?php endforeach; ?>
                    <?php if ($movies === []): ?><tr><td colspan="3" class="empty-table">No movies yet. Add one above.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
            <section class="dashboard-panel users-panel">
                <div class="panel-heading"><div><span class="panel-kicker">YOUR COMMUNITY</span><h2>Manage accounts</h2></div><span class="panel-count"><?= count($users) ?> MEMBERS</span></div>
                <div class="table-wrap"><table class="admin-table"><thead><tr><th>MEMBER</th><th>ROLE</th><th></th></tr></thead><tbody>
                    <?php foreach ($users as $account): ?><tr><td><div class="account-cell"><span class="account-avatar"><?= e(strtoupper(substr($account['name'], 0, 1))) ?></span><span><strong><?= e($account['name']) ?><?= (int) $account['id'] === (int) $admin['id'] ? ' (you)' : '' ?></strong><small><?= e($account['email']) ?></small></span></div></td><td><span class="role-badge <?= $account['role'] === 'admin' ? 'role-admin' : '' ?>"><?= e(ucfirst($account['role'])) ?></span></td><td class="table-action"><?php if ((int) $account['id'] !== (int) $admin['id']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="change_role"><input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>"><input type="hidden" name="role" value="<?= $account['role'] === 'admin' ? 'user' : 'admin' ?>"><button class="remove-button role-action" type="submit"><?= $account['role'] === 'admin' ? 'Make member' : 'Make admin' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
                    <?php if ($users === []): ?><tr><td colspan="3" class="empty-table">No registered members yet.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
        </div>
        <p class="dashboard-hint">To create the first administrator, register an account and promote it with the SQL command in README.txt. After that, you can manage administrator roles here.</p>
    </main>
    <script src="assets/js/app.js" defer></script>
</body>
</html>
