<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$showFavorites = isset($_GET['favorites']) && $_GET['favorites'] === '1';
if ($showFavorites && $user === null) {
    header('Location: login.php?return=favorites');
    exit;
}

$movies = [];
$databaseError = null;
try {
    if ($user !== null) {
        $query = $showFavorites
            ? 'SELECT m.id, m.title, m.poster, 1 AS is_favorite
               FROM movies m
               INNER JOIN favorites f ON f.movie_id = m.id
               WHERE f.user_id = :user_id
               ORDER BY m.title'
            : 'SELECT m.id, m.title, m.poster,
                      EXISTS (
                          SELECT 1 FROM favorites f
                          WHERE f.movie_id = m.id AND f.user_id = :user_id
                      ) AS is_favorite
               FROM movies m
               ORDER BY m.title';
        $statement = database()->prepare($query);
        $statement->execute(['user_id' => (int) $user['id']]);
        $movies = $statement->fetchAll();
    } else {
        $movies = database()->query('SELECT id, title, poster, 0 AS is_favorite FROM movies ORDER BY title')->fetchAll();
    }
} catch (RuntimeException $exception) {
    $databaseError = $exception->getMessage();
    $movies = array_map(static function ($poster) {
        return [
            'title' => pathinfo($poster, PATHINFO_FILENAME),
            'poster' => $poster,
            'is_favorite' => 0,
        ];
    }, movie_posters());
}
$flash = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#060b16">
    <meta name="description" content="Discover your next favorite movie on GODIFLEX.">
    <title>GODIFLEX — Stories worth staying in for</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="GODIFLEX home"><span class="brand-mark">G</span>GODI<span>FLEX</span></a>
        <nav class="main-nav" aria-label="Main navigation">
            <a class="nav-link active" href="#home">Home</a>
            <a class="nav-link" href="#movies">Movies</a>
            <?php if ($user !== null): ?>
                <a class="nav-link <?= $showFavorites ? 'active' : '' ?>" href="index.php?favorites=1#movies">My favorites</a>
            <?php endif; ?>
            <a class="nav-link" href="#about">About</a>
        </nav>
        <div class="header-actions">
            <label class="search-box" aria-label="Search movies">
                <span class="search-icon" aria-hidden="true">⌕</span>
                <input id="movie-search" type="search" placeholder="Find a movie..." autocomplete="off">
                <kbd>/</kbd>
            </label>
            <?php if ($user !== null): ?>
                <a class="button button-quiet favorites-shortcut" href="index.php?favorites=1#movies">♥ Favorites</a>
                <span class="welcome-user">Hi, <?= e($user['name']) ?></span>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="button button-quiet" href="admin.php">Dashboard</a>
                <?php endif; ?>
                <form action="logout.php" method="post" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="button button-outline" type="submit">Log out</button>
                </form>
            <?php else: ?>
                <a class="button button-quiet sign-in" href="login.php">Sign in</a>
                <a class="button button-primary" href="register.php">Get started <span aria-hidden="true">↗</span></a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <?php if ($databaseError !== null): ?>
            <div class="notice notice-error page-notice"><?= e($databaseError) ?></div>
        <?php endif; ?>
        <?php if ($flash !== null): ?>
            <div class="notice notice-<?= e($flash['type']) ?> page-notice"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <section class="hero" id="home">
            <div class="hero-copy">
                <div class="eyebrow"><span class="eyebrow-dot"></span> YOUR NEXT FAVORITE IS HERE</div>
                <h1>Big stories.<br><span>Blue-sky</span> imagination.</h1>
                <p class="hero-description">A little wonder goes a long way. Find your next feel-good favorite and make tonight a movie night.</p>
                <div class="hero-actions">
                    <a class="button button-primary button-large" href="#movies"><span class="play-icon">▶</span> Explore movies</a>
                    <a class="text-link" href="#about">Discover GODIFLEX <span aria-hidden="true">→</span></a>
                </div>
                <div class="hero-proof">
                    <div class="avatar-stack" aria-hidden="true"><span>✦</span><span>G</span><span>✧</span></div>
                    <p><strong>A universe of stories</strong><br>Made for your next movie night</p>
                </div>
            </div>
            <div class="hero-art" aria-label="Featured movie posters">
                <div class="hero-orbit orbit-one"></div>
                <div class="hero-orbit orbit-two"></div>
                <?php
                $featured = array_slice($movies, 0, 4);
                $positions = ['poster-back-left', 'poster-back-right', 'poster-front-left', 'poster-front-right'];
                foreach ($featured as $index => $movie):
                ?>
                    <div class="floating-poster <?= e($positions[$index]) ?>">
                        <img src="Pics/<?= rawurlencode($movie['poster']) ?>" alt="<?= e($movie['title']) ?> poster">
                    </div>
                <?php endforeach; ?>
                <div class="hero-glow"></div>
                <div class="art-caption"><span class="caption-line"></span><span>TONIGHT'S<br><strong>GOOD IDEA</strong></span></div>
            </div>
            <a class="scroll-cue" href="#movies"><span></span> SCROLL TO EXPLORE</a>
        </section>

        <section class="catalog-section" id="movies">
            <div class="section-heading">
                <div>
                    <div class="eyebrow section-eyebrow"><span class="eyebrow-dot"></span> <?= $showFavorites ? 'SAVED JUST FOR YOU' : 'HANDPICKED FOR YOU' ?></div>
                    <h2><?= $showFavorites ? 'Your favorite ' : 'Find your ' ?><span><?= $showFavorites ? 'movie nights.' : 'kind of magic.' ?></span></h2>
                    <p class="section-subtitle"><?= $showFavorites ? 'All the stories you have saved in one place.' : 'Good stories have a way of finding us.' ?></p>
                </div>
                <div class="catalog-controls">
                    <span class="movie-count"><?= count($movies) ?> MOVIES</span>
                    <?php if ($showFavorites): ?>
                        <a class="button button-outline" href="index.php#movies">Browse all</a>
                    <?php endif; ?>
                    <div class="slider-controls">
                        <button class="slider-button" type="button" data-scroll="left" aria-label="Scroll movies left">←</button>
                        <button class="slider-button" type="button" data-scroll="right" aria-label="Scroll movies right">→</button>
                    </div>
                </div>
            </div>
            <?php if ($movies === [] && $databaseError === null): ?>
                <div class="empty-state"><?= $showFavorites ? 'You have not saved any favorites yet. Browse the collection and tap the heart on a movie to save it.' : 'No movies yet. Add titles from the admin dashboard to start your collection.' ?></div>
            <?php else: ?>
                <div class="movie-rail" id="movie-rail">
                    <?php foreach ($movies as $index => $movie): ?>
                        <article class="movie-card" data-title="<?= e(strtolower($movie['title'])) ?>" style="--card-index: <?= (int) $index ?>">
                            <a class="movie-poster" href="Pics/<?= rawurlencode($movie['poster']) ?>" target="_blank" rel="noopener" aria-label="View <?= e($movie['title']) ?> poster">
                                <img src="Pics/<?= rawurlencode($movie['poster']) ?>" alt="<?= e($movie['title']) ?> movie poster" loading="lazy">
                                <span class="poster-shade"></span>
                                <span class="poster-play" aria-hidden="true">▶</span>
                                <span class="poster-tag">GODIFLEX PICK</span>
                            </a>
                            <div class="movie-meta"><h3><?= e($movie['title']) ?></h3><span class="movie-year">FEATURED</span></div>
                            <p class="movie-subtitle">A story to remember</p>
                            <?php if ($user !== null): ?>
                                <form action="favorite.php" method="post" class="favorite-form">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">
                                    <input type="hidden" name="return_to" value="<?= $showFavorites ? 'favorites' : 'movies' ?>">
                                    <input type="hidden" name="action" value="<?= (int) $movie['is_favorite'] === 1 ? 'remove' : 'add' ?>">
                                    <button class="favorite-button <?= (int) $movie['is_favorite'] === 1 ? 'is-favorite' : '' ?>" type="submit" aria-label="<?= (int) $movie['is_favorite'] === 1 ? 'Remove ' . e($movie['title']) . ' from favorites' : 'Add ' . e($movie['title']) . ' to favorites' ?>">
                                        <span aria-hidden="true"><?= (int) $movie['is_favorite'] === 1 ? '♥' : '♡' ?></span>
                                        <?= (int) $movie['is_favorite'] === 1 ? 'Saved' : 'Add to favorites' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a class="favorite-button" href="login.php?return=favorites"><span aria-hidden="true">♡</span> Sign in to save</a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="no-results" id="no-results" hidden>No movies match your search. Try another title.</p>
            <?php endif; ?>
        </section>

        <section class="feature-banner" id="about">
            <div class="banner-sparkle sparkle-one">✦</div>
            <div class="banner-sparkle sparkle-two">✧</div>
            <div class="banner-content">
                <span class="banner-kicker">A BETTER KIND OF MOVIE NIGHT</span>
                <h2>Press play on<br><span>something wonderful.</span></h2>
                <p>Your seat in the front row is waiting.</p>
                <?php if ($user === null): ?>
                    <a class="button button-light" href="register.php">Join the story <span aria-hidden="true">→</span></a>
                <?php else: ?>
                    <a class="button button-light" href="#movies">Pick a movie <span aria-hidden="true">→</span></a>
                <?php endif; ?>
            </div>
            <div class="banner-decoration" aria-hidden="true"><div class="banner-ring ring-outer"></div><div class="banner-ring ring-middle"></div><div class="banner-ring ring-inner"><span>G</span></div><span class="banner-star">✦</span></div>
        </section>
    </main>

    <footer class="site-footer">
        <a class="brand footer-brand" href="index.php"><span class="brand-mark">G</span>GODI<span>FLEX</span></a>
        <p>Stories worth staying in for.</p>
        <span>© <?= date('Y') ?> GODIFLEX · MADE FOR MOVIE NIGHTS</span>
    </footer>
    <script src="assets/js/app.js" defer></script>
</body>
</html>
