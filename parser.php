<?php

function parseWiki($body)
{
    $htmlBody = $body;

    /*
    |--------------------------------------------------------------------------
    | Infoboxes {{box ...}}
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace_callback('/\{\{box\s*(.*?)\}\}/s', function ($matches) {

        $raw = trim($matches[1]);
        $parts = preg_split('/\s*\|\s*/', $raw);

        $fields = [];

        foreach ($parts as $part) {

            if (trim($part) === '') {
                continue;
            }

            $pair = explode('=', $part, 2);

            if (count($pair) !== 2) {
                continue;
            }

            $key = trim($pair[0], " \t\n\r\0\x0B\"'");
            $value = trim($pair[1], " \t\n\r\0\x0B\"'");

            $fields[$key] = $value;
        }

        $html = '<aside class="wiki-box">';

        if (!empty($fields['title'])) {
            $html .= '<div class="title">' .
                htmlspecialchars($fields['title']) .
                '</div>';
            unset($fields['title']);
        }

        if (!empty($fields['img'])) {
            $html .= '<img src="' .
                htmlspecialchars($fields['img']) .
                '" alt="">';
            unset($fields['img']);
        }

        if (!empty($fields['caption'])) {
            $html .= '<div class="caption">' .
                htmlspecialchars($fields['caption']) .
                '</div>';
            unset($fields['caption']);
        }

        if (!empty($fields['section'])) {
            $html .= '<div class="section">' .
                htmlspecialchars($fields['section']) .
                '</div>';
            unset($fields['section']);
        }

        foreach ($fields as $key => $value) {
            $html .= '<b>' . htmlspecialchars($key) . '</b>';
            $html .= '<div>' . htmlspecialchars($value) . '</div>';
        }

        $html .= '</aside>';

        return $html;

    }, $htmlBody);

    // return $htmlBody;
    /*
    |--------------------------------------------------------------------------
    | Code blocks ``` ... ```
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace_callback('/```(.*?)```/s', function ($m) {
        return '<pre><code>' .
            htmlspecialchars(trim($m[1])) .
            '</code></pre>';
    }, $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Inline code
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace(
        '/`([^`\n]+)`/',
        '<code>$1</code>',
        $htmlBody
    );

    /*
    |--------------------------------------------------------------------------
    | Internal links [[Page]] [[Page|Alias]]
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace_callback('/\[\[(.*?)\]\]/', function ($m) {

        $parts = explode('|', $m[1], 2);

        $page = trim($parts[0]);
        $text = isset($parts[1]) ? trim($parts[1]) : $page;

        $slug = strtolower(str_replace(' ', '-', $page));

        return '<a href="page.php?slug=' .
            urlencode($slug) .
            '">' .
            htmlspecialchars($text) .
            '</a>';

    }, $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Footnotes [ref:https://...]
    |--------------------------------------------------------------------------
    */

    $refs = [];

    $htmlBody = preg_replace_callback('/\[ref:(.*?)\]/', function ($m) use (&$refs) {

        $url = trim($m[1]);
        $refs[] = $url;
        $num = count($refs);

        return '<sup><a href="#ref' . $num . '">[' . $num . ']</a></sup>';

    }, $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace('/^===\s*(.*?)\s*$/m', '<h4>$1</h4>', $htmlBody);
    $htmlBody = preg_replace('/^==\s*(.*?)\s*$/m', '<h3>$1</h3>', $htmlBody);
    $htmlBody = preg_replace('/^=\s*(.*?)\s*$/m', '<h2>$1</h2>', $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Blockquotes
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace('/^>\s*(.*?)$/m', '<blockquote>$1</blockquote>', $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Unordered lists
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $htmlBody);
    $htmlBody = preg_replace('/(<li>.*?<\/li>\s*)+/s', '<ul>$0</ul>', $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Ordered lists
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace('/^# (.*?)$/m', '<li>$1</li>', $htmlBody);
    $htmlBody = preg_replace('/(<li>.*?<\/li>\s*)+/s', '<ol>$0</ol>', $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace_callback('/\{\|(.*?)\|\}/s', function ($m) {

        $rows = preg_split('/\n/', trim($m[1]));
        $html = '<table border="1">';

        foreach ($rows as $row) {

            $row = trim($row);

            if ($row === '') {
                continue;
            }

            $cells = explode('|', trim($row, '|'));

            $html .= '<tr>';

            foreach ($cells as $cell) {

                $cell = trim($cell);

                if (preg_match("/^''(.*?)''$/", $cell, $h)) {
                    $html .= '<th>' . $h[1] . '</th>';
                } else {
                    $html .= '<td>' . $cell . '</td>';
                }
            }

            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;

    }, $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Inline formatting
    |--------------------------------------------------------------------------
    */

    $htmlBody = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<b><i>$1</i></b>', $htmlBody);
    $htmlBody = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $htmlBody);
    $htmlBody = preg_replace('/\*(.*?)\*/s', '<i>$1</i>', $htmlBody);

    $htmlBody = preg_replace('/__(.*?)__/s', '<u>$1</u>', $htmlBody);
    $htmlBody = preg_replace('/--(.*?)--/s', '<del>$1</del>', $htmlBody);

    /*
    |--------------------------------------------------------------------------
    | Paragraphs
    |--------------------------------------------------------------------------
    */

    $parts = preg_split('/\n\s*\n/', $htmlBody);

    foreach ($parts as &$p) {

        if (!preg_match('/^\s*<(h2|h3|h4|ul|ol|li|blockquote|table|pre|aside)/', $p)) {
            $p = '<p>' . trim($p) . '</p>';
        }
    }

    $htmlBody = implode("\n", $parts);

    /*
    |--------------------------------------------------------------------------
    | References section
    |--------------------------------------------------------------------------
    */

    if ($refs) {

        $htmlBody .= '<br><br><br><h2>References</h2><hr><ol>';

        foreach ($refs as $i => $url) {

            $n = $i + 1;

            $htmlBody .= '<li id="ref' . $n . '"><a href="' .
                htmlspecialchars($url) .
                '">' .
                htmlspecialchars($url) .
                '</a></li>';
        }

        $htmlBody .= '</ol>';
    }

    return $htmlBody;
}