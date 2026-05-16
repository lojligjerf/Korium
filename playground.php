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
<link rel="stylesheet" href="style.css">
<style>

/* Layout */

body {
    margin: 0;
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
| Live parser
|--------------------------------------------------------------------------
*/

const textarea = document.getElementById('markup');

textarea.addEventListener('input', updatePreview);

function escapeHtml(text) {

    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}

function updatePreview() {

    let text = textarea.value;

    /*
    |--------------------------------------------------------------------------
    | Code blocks
    |--------------------------------------------------------------------------
    */

    text = text.replace(/```([\s\S]*?)```/g, (m, code) => {
        return `<pre><code>${escapeHtml(code.trim())}</code></pre>`;
    });

     /*
    |--------------------------------------------------------------------------
    | Infoboxes {{box ...}}
    |--------------------------------------------------------------------------
    */

    text = text.replace(/\{\{box\s*([\s\S]*?)\}\}/g, (m, raw) => {

        const parts = raw.split(/\s*\|\s*/);

        let fields = {};

        parts.forEach(part => {

            if (!part.includes('=')) return;

            let pair = part.split('=');

            let key = pair[0].trim().replace(/^["']|["']$/g, '');
            let value = pair.slice(1).join('=').trim().replace(/^["']|["']$/g, '');

            fields[key] = value;
        });

        let html = '<aside class="wiki-box">';

        /*
        |--------------------------------------------------------------------------
        | Standard fields
        |--------------------------------------------------------------------------
        */

        if (fields.title) {
            html += `<div class="title">${fields.title}</div>`;
            delete fields.title;
        }

        if (fields.img) {
            html += `<img src="${fields.img}" alt="">`;
            delete fields.img;
        }

        if (fields.caption) {
            html += `<div class="caption">${fields.caption}</div>`;
            delete fields.caption;
        }

        if (fields.section) {
            html += `<div class="section">${fields.section}</div>`;
            delete fields.section;
        }

        /*
        |--------------------------------------------------------------------------
        | Remaining fields
        |--------------------------------------------------------------------------
        */

        Object.entries(fields).forEach(([key, value]) => {

            html += `<b>${key}</b>`;
            html += `<div>${value}</div>`;
        });

        html += '</aside>';

        return html;
    });
    /*
    |--------------------------------------------------------------------------
    | Inline code
    |--------------------------------------------------------------------------
    */

    text = text.replace(/`([^`\n]+)`/g, '<code>$1</code>');

    /*
    |--------------------------------------------------------------------------
    | Internal links
    |--------------------------------------------------------------------------
    */

    text = text.replace(/\[\[(.*?)\]\]/g, (m, content) => {

        const parts = content.split('|');

        const page = parts[0].trim();
        const label = parts[1] ? parts[1].trim() : page;

        const slug = page.toLowerCase().replace(/\s+/g, '-');

        return `<a href="page.php?slug=${slug}">${label}</a>`;
    });

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    */

    text = text.replace(/^===\s*(.*?)\s*$/gm, '<h4>$1</h4>');
    text = text.replace(/^==\s*(.*?)\s*$/gm, '<h3>$1</h3>');
    text = text.replace(/^=\s*(.*?)\s*$/gm, '<h2>$1</h2>');

    /*
    |--------------------------------------------------------------------------
    | Blockquotes
    |--------------------------------------------------------------------------
    */

    text = text.replace(/^>\s*(.*?)$/gm, '<blockquote>$1</blockquote>');

    /*
    |--------------------------------------------------------------------------
    | Unordered lists
    |--------------------------------------------------------------------------
    */

    text = text.replace(/^- (.*?)$/gm, '<uli>$1</uli>');
    text = text.replace(/(<uli>.*?<\/uli>\s*)+/gs, '<ul>$&</ul>');
    text = text.replace(/<uli>/g, '<li>');
    text = text.replace(/<\/uli>/g, '</li>');

    /*
    |--------------------------------------------------------------------------
    | Ordered lists
    |--------------------------------------------------------------------------
    */

    text = text.replace(/^# (.*?)$/gm, '<oli>$1</oli>');
    text = text.replace(/(<oli>.*?<\/oli>\s*)+/gs, '<ol>$&</ol>');
    text = text.replace(/<oli>/g, '<li>');
    text = text.replace(/<\/oli>/g, '</li>');

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    */

    text = text.replace(/\{\|([\s\S]*?)\|\}/g, (m, content) => {

        let rows = content.trim().split('\n');

        let html = '<table border="1">';

        rows.forEach(row => {

            row = row.trim();

            if (!row) return;

            let cells = row.replace(/^\|/, '').replace(/\|$/, '').split('|');

            html += '<tr>';

            cells.forEach(cell => {

                cell = cell.trim();

                if (/^''(.*?)''$/.test(cell)) {
                    html += `<th>${cell.slice(2, -2)}</th>`;
                } else {
                    html += `<td>${cell}</td>`;
                }

            });

            html += '</tr>';

        });

        html += '</table>';

        return html;
    });

    /*
    |--------------------------------------------------------------------------
    | Inline formatting
    |--------------------------------------------------------------------------
    */

    text = text.replace(/\*\*\*(.*?)\*\*\*/gs, '<b><i>$1</i></b>');
    text = text.replace(/\*\*(.*?)\*\*/gs, '<b>$1</b>');
    text = text.replace(/\*(.*?)\*/gs, '<i>$1</i>');

    text = text.replace(/__(.*?)__/gs, '<u>$1</u>');
    text = text.replace(/--(.*?)--/gs, '<del>$1</del>');

    /*
    |--------------------------------------------------------------------------
    | Paragraphs
    |--------------------------------------------------------------------------
    */

    let parts = text.split(/\n\s*\n/);

    parts = parts.map(p => {

        if (/^\s*<(h2|h3|h4|ul|ol|blockquote|table|pre)/.test(p)) {
            return p;
        }

        return `<p>${p.trim()}</p>`;
    });

    text = parts.join('\n');

    preview.innerHTML = text;
}

/*
|--------------------------------------------------------------------------
| Initial render
|--------------------------------------------------------------------------
*/

updatePreview();

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

    textarea.value = `= Header 1
== Header 2
=== Header 3
*Italic*, **Bold**, and ***Both***.

\`inline code\`

__Underline__ and --strikethrough--.

[[template|Internal Links]]

- Unordered list

# Ordered list

> Blockquote

{|
| ''Header 1'' | ''Header 2'' |
| Data 1 | Data 2 |
|}

{{box | title="Title" | img="https://upload.wikimedia.org/wikipedia/commons/0/0e/DefaultImage.png" | caption="Caption" | section="Section" | "Header"="Value" }}

\`\`\`
code block
\`\`\`

== Reference

Example[ref:https://example.com]
`;

    updatePreview();
}

</script>

</body>
</html>