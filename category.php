<?php

/*
|--------------------------------------------------------------------------
| category.php
|--------------------------------------------------------------------------
| Example:
| /category.php?name=Government
|--------------------------------------------------------------------------
*/

$category = $_GET['name'] ?? '';
$category = trim($category);

/*
|--------------------------------------------------------------------------
| Validate input
|--------------------------------------------------------------------------
*/

if ($category === '') {
    die("No category specified.");
}

/*
|--------------------------------------------------------------------------
| Load category index
|--------------------------------------------------------------------------
*/

$file = __DIR__ . '/meta/category-index.json';

if (!file_exists($file)) {
    die("Category index not found.");
}

$json = file_get_contents($file);
$index = json_decode($json, true);

if (!$index) {
    die("Invalid category index.");
}

/*
|--------------------------------------------------------------------------
| Find category (case-insensitive)
|--------------------------------------------------------------------------
*/

$matchedCategory = null;
$pages = [];

foreach ($index as $catName => $items) {

    if (strcasecmp($catName, $category) === 0) {
        $matchedCategory = $catName;
        $pages = $items;
        break;
    }
}

if ($matchedCategory === null) {
    $matchedCategory = $category;
    $pages = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Category: <?= htmlspecialchars($matchedCategory) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Category: <?= htmlspecialchars($matchedCategory) ?></h1>
    <hr>
    <p><?= count($pages) ?> page(s) in this category.</p>
</header>

<main>

<?php if (empty($pages)): ?>

    <p>No pages found in this category.</p>

<?php else: ?>

    <ul class="category-list">

    <?php foreach ($pages as $page): ?>

        <li class="category-item">
            <h3>
                <a href="page.php?slug=<?= urlencode($page['slug']) ?>">
                    <?= htmlspecialchars($page['title']) ?>
                </a>
            </h3>
        </li>

    <?php endforeach; ?>

    </ul>

<?php endif; ?>

</main>

<footer>
    <p><a href="index.php">Return to homepage</a></p>
</footer>

</body>
</html>