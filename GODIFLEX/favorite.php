<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#movies');
    exit;
}

verify_csrf();
$returnTo = ($_POST['return_to'] ?? '') === 'favorites' ? 'favorites' : 'movies';
$destination = $returnTo === 'favorites' ? 'index.php?favorites=1#movies' : 'index.php#movies';
$user = current_user();
if ($user === null) {
    set_flash('Sign in to save movies to your favorites.', 'error');
    header('Location: login.php?return=favorites');
    exit;
}

$movieId = filter_var($_POST['movie_id'] ?? null, FILTER_VALIDATE_INT);
$action = (string) ($_POST['action'] ?? '');
if ($movieId === false || $movieId === null || $movieId < 1 || !in_array($action, ['add', 'remove'], true)) {
    set_flash('That favorite action is not valid.', 'error');
    header('Location: ' . $destination);
    exit;
}

$connection = database();
$movieStatement = $connection->prepare('SELECT title FROM movies WHERE id = :id');
$movieStatement->execute(['id' => $movieId]);
$title = $movieStatement->fetchColumn();
if ($title === false) {
    set_flash('That movie could not be found.', 'error');
    header('Location: ' . $destination);
    exit;
}

if ($action === 'add') {
    $statement = $connection->prepare(
        'INSERT IGNORE INTO favorites (user_id, movie_id) VALUES (:user_id, :movie_id)'
    );
    $statement->execute([
        'user_id' => (int) $user['id'],
        'movie_id' => $movieId,
    ]);
    set_flash($statement->rowCount() > 0 ? $title . ' was added to your favorites.' : $title . ' is already in your favorites.');
} else {
    $statement = $connection->prepare(
        'DELETE FROM favorites WHERE user_id = :user_id AND movie_id = :movie_id'
    );
    $statement->execute([
        'user_id' => (int) $user['id'],
        'movie_id' => $movieId,
    ]);
    set_flash($statement->rowCount() > 0 ? $title . ' was removed from your favorites.' : $title . ' was not in your favorites.');
}

header('Location: ' . $destination);
exit;
