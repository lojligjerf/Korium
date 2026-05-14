<?php

require_once __DIR__ . '/parser.php';

/*
|--------------------------------------------------------------------------
| 1. Get slug safely
|--------------------------------------------------------------------------
*/

$slug = $_GET['slug'] ?? '';
$slug = strtolower(trim($slug));

if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
    die("Invalid page name.");
}

/*
|--------------------------------------------------------------------------
| 2. Load file
|--------------------------------------------------------------------------
*/

$file = __DIR__ . '/content/' . $slug . '.json';

if (!file_exists($file)) {
    http_response_code(404);
    die("Page not found.");
}

/*
|--------------------------------------------------------------------------
| 3. Decode JSON
|--------------------------------------------------------------------------
*/

$json = file_get_contents($file);
$page = json_decode($json, true);

if (!$page) {
    die("Invalid JSON.");
}

/*
|--------------------------------------------------------------------------
| 4. Metadata
|--------------------------------------------------------------------------
*/

$title      = $page['title'] ?? 'Untitled';
$namespace    = $page['namespace'] ?? '';
$categories = $page['categories'] ?? [];
$body       = $page['body'] ?? '';

/*
|--------------------------------------------------------------------------
| 5. Parse wiki body
|--------------------------------------------------------------------------
*/

$htmlBody = parseWiki($body);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($namespace ? $namespace . ':' . $title : $title) ?> - Korium Wiki</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
<h1><?= htmlspecialchars($namespace ? $namespace . ':' . $title : $title) ?></h1>
<hr>
</header>

<main>
<?= $htmlBody ?>
</main>

<footer>

<?php if ($categories): ?>
<p>
<i>Categories:</i>

<?php foreach ($categories as $cat): ?>
<a href="category.php?name=<?= urlencode($cat) ?>">
<?= htmlspecialchars($cat) ?>
</a>
<?php endforeach; ?>

</p>
<?php endif; ?>

</footer>

</body>
</html>