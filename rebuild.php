<?php

$contentDir = __DIR__ . '/content/';
$metaDir    = __DIR__ . '/meta/';

if (!is_dir($metaDir)) {
    mkdir($metaDir, 0777, true);
}

$files = glob($contentDir . '*.json');

$pageIndex = [];
$categoryIndex = [];

/*
|--------------------------------------------------------------------------
| Scan all pages
|--------------------------------------------------------------------------
*/

foreach ($files as $file) {

    $json = file_get_contents($file);
    $page = json_decode($json, true);

    if (!$page) {
        continue;
    }

    $title      = $page['title'] ?? 'Untitled';
    $slug       = $page['slug'] ?? basename($file, '.json');
    $categories = $page['categories'] ?? [];

    /*
    |--------------------------------------------------------------------------
    | Build page index
    |--------------------------------------------------------------------------
    */

    $pageIndex[] = [
        'title'      => $title,
        'slug'       => $slug,
        'categories' => $categories
    ];

    /*
    |--------------------------------------------------------------------------
    | Build category index
    |--------------------------------------------------------------------------
    */

    foreach ($categories as $cat) {

        if (!isset($categoryIndex[$cat])) {
            $categoryIndex[$cat] = [];
        }

        $categoryIndex[$cat][] = [
            'title'   => $title,
            'slug'    => $slug
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Sort pages alphabetically
|--------------------------------------------------------------------------
*/

usort($pageIndex, fn($a, $b) => strcmp($a['title'], $b['title']));

/*
|--------------------------------------------------------------------------
| Sort category pages
|--------------------------------------------------------------------------
*/

ksort($categoryIndex);

foreach ($categoryIndex as &$pages) {
    usort($pages, fn($a, $b) => strcmp($a['title'], $b['title']));
}

/*
|--------------------------------------------------------------------------
| Save files
|--------------------------------------------------------------------------
*/

file_put_contents(
    $metaDir . 'index.json',
    json_encode($pageIndex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

file_put_contents(
    $metaDir . 'category-index.json',
    json_encode($categoryIndex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo "Indexes rebuilt successfully.";