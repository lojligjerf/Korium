<?php
require_once __DIR__ . '/parser.php';

$input = $_POST['markup'] ?? '';
$output = $input ? parseWiki($input) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Korium Wiki markup tester</title>

<style>

/* Layout */

body {
    margin: 0;
    font-family: sans-serif;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

header {
    padding: 10px;
    background: #f0f0f0;
    border-bottom: 1px solid #ccc;
}

main {
    flex: 1;
    display: flex;
    overflow: hidden;
}

/* Panels */

.panel {
    height: 100%;
    overflow: auto;
}

#editor {
    width: 50%;
    display: flex;
    flex-direction: column;
}

#preview {
    width: 50%;
    padding: 15px;
    border-left: 1px solid #ccc;
    background: white;
}

/* Textarea */

textarea {
    flex: 1;
    width: 100%;
    border: none;
    padding: 10px;
    font-family: monospace;
    font-size: 14px;
    resize: none;
    outline: none;
}

/* Divider */

#divider {
    width: 5px;
    cursor: col-resize;
    background: #ddd;
}

/* Buttons */

button {
    margin-right: 10px;
}

.controls {
    margin-bottom: 5px;
}

/* Basic content styling */

#preview h2, #preview h3, #preview h4 {
    margin-top: 1em;
}

#preview ul, #preview ol {
    padding-left: 20px;
}

#preview table {
    border-collapse: collapse;
}

#preview td, #preview th {
    padding: 5px;
    border: 1px solid #aaa;
}

</style>
</head>
<body>

<header>
    <form method="POST" id="form">
        <div class="controls">
            <button type="submit">Render</button>
            <button type="button" onclick="loadExample()">Load Example</button>
            <button type="button" onclick="clearText()">Clear</button>
        </div>
    </form>
</header>

<main>

    <div id="editor" class="panel">
        <textarea id="markup" name="markup" form="form"><?= htmlspecialchars($input) ?></textarea>
    </div>

    <div id="divider"></div>

    <div id="preview" class="panel">
        <?= $output ?>
    </div>

</main>

<script>

/*
|--------------------------------------------------------------------------
| Resizable split screen
|--------------------------------------------------------------------------
*/

const divider = document.getElementById('divider');
const editor = document.getElementById('editor');
const preview = document.getElementById('preview');

let dragging = false;

divider.addEventListener('mousedown', () => dragging = true);

document.addEventListener('mouseup', () => dragging = false);

document.addEventListener('mousemove', (e) => {
    if (!dragging) return;

    const percent = (e.clientX / window.innerWidth) * 100;

    editor.style.width = percent + '%';
    preview.style.width = (100 - percent) + '%';
});

/*
|--------------------------------------------------------------------------
| Live preview (debounced)
|--------------------------------------------------------------------------
*/

const textarea = document.getElementById('markup');

let timeout = null;

textarea.addEventListener('input', () => {

    clearTimeout(timeout);

    timeout = setTimeout(() => {
        updatePreview();
    }, 300);

});

function updatePreview() {

    fetch('playground.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'markup=' + encodeURIComponent(textarea.value)
    })
    .then(res => res.text())
    .then(html => {

        const doc = new DOMParser().parseFromString(html, 'text/html');

        const newPreview = doc.getElementById('preview');

        if (newPreview) {
            preview.innerHTML = newPreview.innerHTML;
        }

    });
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function clearText() {
    textarea.value = '';
    preview.innerHTML = '';
}

function loadExample() {

    textarea.value = `= Example Page

This is *italic*, **bold**, and ***both***.

__Underline__ and --strikethrough--.

[[Main Page|Home]]

== List

- Item one
- Item two

# First
# Second

> This is a quote

== Table

{|
| ''Name'' | ''Role'' |
| Alice | Leader |
| Bob | Minister |
|}

== Infobox

{{box | title="Example" | section="Info" | "Founded"="2024" }}

== Code

\`\`\`
function test() {
    return true;
}
\`\`\`

== Reference

Example[ref:https://example.com]
`;

    updatePreview();
}

</script>

</body>
</html>