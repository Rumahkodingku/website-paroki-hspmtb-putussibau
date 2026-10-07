<?php

use App\Services\HtmlSanitizer;

beforeEach(function () {
    $this->sanitizer = app(HtmlSanitizer::class);
});

/*
|--------------------------------------------------------------------------
| T24 - Sanitizer XSS - Removed
|--------------------------------------------------------------------------
|
| Every payload here is one an editor, a paste from a website, or a hand-crafted
| POST could produce. The assertion is deliberately blunt, checking for the
| dangerous substring anywhere in the output rather than for an exact string:
| the point of the test is that the payload is gone, not that the sanitizer
| happens to produce a particular whitespace.
|
*/

test('dangerous html is removed', function (string $html, string $needle) {
    expect($this->sanitizer->clean($html))
        ->not
        ->toContain($needle);
})->with([
    'script element' => ['<script>alert(1)</script>', 'script'],
    'script body' => ['<script>alert(1)</script>', 'alert(1)'],
    'img onerror' => ['<img src="x" onerror="alert(1)">', 'onerror'],
    'svg onload' => ['<svg onload="alert(1)"></svg>', 'onload'],
    'div onclick' => ['<div onclick="alert(1)">halo</div>', 'onclick'],
    'javascript href' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
    'vbscript href' => ['<a href="vbscript:msgbox(1)">x</a>', 'vbscript:'],
    'data href' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>', 'data:text/html'],
    'style attribute' => ['<p style="position:fixed;top:0">x</p>', 'style='],
    'style element' => ['<style>body{display:none}</style>', 'display:none'],
    'iframe' => ['<iframe src="https://evil.test"></iframe>', 'iframe'],
    'form and input' => ['<form><input name="a"></form>', '<input'],
    'object' => ['<object data="x.swf"></object>', '<object'],
    'class attribute' => ['<p class="text-red-500">x</p>', 'text-red-500'],
    'id attribute' => ['<p id="tracked">x</p>', 'tracked'],
    'formaction on button' => ['<button formaction="https://evil.test">x</button>', 'formaction'],
    'a data attribute' => ['<a href="https://ok.test" data-track="x">y</a>', 'data-track'],
    'base64 media scheme' => ['<img src="data:image/svg+xml;base64,PHN2Zz4=" alt="a">', 'data:image'],
]);

test('an attribute the allowlist never listed does not survive', function () {
    $clean = $this->sanitizer->clean('<p style="x" class="y" id="z" onclick="w" data-a="b" lang="id">teks</p>');

    expect($clean)->toBe('<p>teks</p>');
});

test('a raw text element loses its contents, not just its tag', function () {
    // <style> and <title> hold text rather than markup, so dropping the element
    // alone leaves that text behind and a pasted stylesheet becomes the visible
    // string "p{color:red}" in the middle of the article.
    expect($this->sanitizer->clean('<style>p{color:red}</style><p>Teks</p>'))
        ->toBe('<p>Teks</p>')
        ->and($this->sanitizer->clean('<title>Judul dokumen</title><p>Teks</p>'))
        ->toBe('<p>Teks</p>')
        ->and($this->sanitizer->clean('<textarea>halo</textarea>'))
        ->toBe('');
});

test('an unterminated raw text element takes the rest with it', function () {
    // Per the HTML spec everything after an unclosed <style> is its content, so
    // truncating is what a browser would do and what the author meant.
    expect($this->sanitizer->clean('<p>sebelum</p><style>a{'))->toBe('<p>sebelum</p>');
});

test('a dropped element takes its contents with it', function () {
    // Blocked rather than dropped would leave alert(1) sitting in the document
    // as visible text on a parish website.
    expect($this->sanitizer->clean('<p>sebelum</p><script>alert(1)</script><p>sesudah</p>'))
        ->not->toContain('alert')
        ->and($this->sanitizer->clean('<style>p{color:red}</style>'))
        ->not->toContain('color:red');
});

/*
|--------------------------------------------------------------------------
| T25 - Safe HTML - Preserved
|--------------------------------------------------------------------------
|
| The other half of the requirement. A sanitizer that removes everything passes
| every test above and destroys the content, so these assert the allowlist
| survives a round trip with its structure intact.
|
*/

test('safe html is preserved', function (string $html, string $expected) {
    expect($this->sanitizer->clean($html))->toBe($expected);
})->with([
    'paragraph' => ['<p>Teks paroki.</p>', '<p>Teks paroki.</p>'],
    'bold' => ['<p><strong>Tebal</strong></p>', '<p><strong>Tebal</strong></p>'],
    'italic' => ['<p><em>Miring</em></p>', '<p><em>Miring</em></p>'],
    'underline' => ['<p><u>Garis bawah</u></p>', '<p><u>Garis bawah</u></p>'],
    'hard break' => ['<p>Satu<br />Dua</p>', '<p>Satu<br />Dua</p>'],
    'bullet list' => ['<ul><li>Satu</li><li>Dua</li></ul>', '<ul><li>Satu</li><li>Dua</li></ul>'],
    'ordered list' => ['<ol><li>Satu</li><li>Dua</li></ol>', '<ol><li>Satu</li><li>Dua</li></ol>'],
    'blockquote' => ['<blockquote>Kutipan</blockquote>', '<blockquote>Kutipan</blockquote>'],
    'h2' => ['<h2>Sub</h2>', '<h2>Sub</h2>'],
    'h3' => ['<h3>Sub</h3>', '<h3>Sub</h3>'],
    'h4' => ['<h4>Sub</h4>', '<h4>Sub</h4>'],
    'figure' => [
        '<figure><figcaption>Keterangan</figcaption></figure>',
        '<figure><figcaption>Keterangan</figcaption></figure>',
    ],
    'table' => [
        '<table><thead><tr><th scope="col">Jam</th></tr></thead><tbody><tr><td>07.00</td></tr></tbody></table>',
        '<table><thead><tr><th scope="col">Jam</th></tr></thead><tbody><tr><td>07.00</td></tr></tbody></table>',
    ],
    'table cell span' => [
        '<table><tbody><tr><td colspan="2">Gabung</td></tr></tbody></table>',
        '<table><tbody><tr><td colspan="2">Gabung</td></tr></tbody></table>',
    ],
]);

test('an external link keeps its href and gains noopener noreferrer', function () {
    expect($this->sanitizer->clean('<a href="https://paroki.test" target="_blank">Situs</a>'))
        ->toBe('<a href="https://paroki.test" target="_blank" rel="noopener noreferrer">Situs</a>');
});

test('an unsafe scheme costs the href but keeps the text', function () {
    expect($this->sanitizer->clean('<a href="javascript:alert(1)">Baca</a>'))
        ->toBe('<a rel="noopener noreferrer">Baca</a>');
});

test('a relative href survives', function () {
    expect($this->sanitizer->clean('<a href="/admin/pengaturan">Pengaturan</a>'))
        ->toContain('href="/admin/pengaturan"');
});

test('telephone and email schemes survive', function () {
    // Symfony entity-encodes @ and + inside attribute values. That is correct
    // HTML which renders as the original characters, so the assertions compare
    // decoded text rather than the exact serialization.
    $tel = $this->sanitizer->clean('<a href="tel:+6281234567890">Telepon</a>');
    $email = $this->sanitizer->clean('<a href="mailto:sekretaris@paroki.test">Surel</a>');

    expect(html_entity_decode($tel, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        ->toContain('href="tel:+6281234567890"')
        ->and(html_entity_decode($email, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
        ->toContain('href="mailto:sekretaris@paroki.test"');
});

test('a published image variant survives with a relative src', function () {
    // Media variants live under /storage with no scheme and no host, so the
    // relative-media allowance is what keeps every image in the application
    // from breaking the next time a rich text field is saved.
    expect($this->sanitizer->clean('<img src="/storage/media/variants/abc.webp" alt="Misa" />'))
        ->toBe('<img src="/storage/media/variants/abc.webp" alt="Misa" />');
});

/*
|--------------------------------------------------------------------------
| Behaviour the allowlist alone does not describe
|--------------------------------------------------------------------------
*/

test('an h1 is promoted to h2 rather than being stripped', function () {
    expect($this->sanitizer->clean('<h1>Judul</h1><p>Isi</p>'))
        ->toBe('<h2>Judul</h2><p>Isi</p>');
});

test('an h1 keeps nothing that could have been smuggled through it', function () {
    expect($this->sanitizer->clean('<h1 onclick="alert(1)" class="x">Judul</h1>'))
        ->toBe('<h2>Judul</h2>');
});

test('bold and italic become the tags the allowlist does have', function () {
    expect($this->sanitizer->clean('<p><b>Tebal</b> dan <i>Miring</i></p>'))
        ->toBe('<p><strong>Tebal</strong> dan <em>Miring</em></p>');
});

test('normalising bold does not damage tags that start with b', function () {
    // <b> must not swallow <blockquote>, <br> or <body>.
    expect($this->sanitizer->clean('<blockquote>Kutip</blockquote><p>a<br />b</p>'))
        ->toBe('<blockquote>Kutip</blockquote><p>a<br />b</p>');
});

test('an unknown tag costs its tag but keeps its words', function () {
    // The reason default_action is block rather than Symfony's drop default.
    expect($this->sanitizer->clean('<div><p>Paragraf penting</p></div>'))
        ->toBe('<p>Paragraf penting</p>');
});

test('escaped markup is left as text', function () {
    expect($this->sanitizer->clean('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>'))
        ->toBe('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');
});

test('an empty or absent value becomes an empty string', function (?string $input) {
    expect($this->sanitizer->clean($input))->toBe('');
})->with(['null' => [null], 'empty' => [''], 'whitespace' => ["  \n "]]);

test('cleaning is idempotent', function () {
    // Saving a field twice, or reading it back and saving it again, must not
    // keep changing the stored value.
    $once = $this->sanitizer->clean('<h1>Judul</h1><b>Tebal</b><a href="https://a.test" target="_blank">x</a>');

    expect($this->sanitizer->clean($once))->toBe($once)
        ->and($this->sanitizer->isClean($once))->toBeTrue();
});

test('plain text extraction keeps the word boundary between blocks', function () {
    expect($this->sanitizer->toPlainText('<p>Halo <strong>dunia</strong></p><p>Baris baru</p>'))
        ->toBe('Halo dunia Baris baru');
});

test('plain text extraction drops markup entirely', function () {
    expect($this->sanitizer->toPlainText('<p>sebelum</p><script>alert(1)</script><p>sesudah</p>'))
        ->toBe('sebelum sesudah');
});

test('plain text extraction collapses whitespace', function () {
    expect($this->sanitizer->toPlainText("<p>Satu\n\n   dua</p>"))->toBe('Satu dua');
});

/*
|--------------------------------------------------------------------------
| The allowlist itself
|--------------------------------------------------------------------------
|
| A tripwire. If a tag is added to config/html.php this fails until the reason is
| written down, and if a tag is removed it fails until the PRD reference is
| updated. The same list has to be reflected in the editor's own configuration,
| because an editor that can emit a tag the sanitizer will delete is worse than
| no editor at all.
|
*/

test('the allowlist is exactly the tags the prd lists', function () {
    expect(array_keys((array) config('html.elements')))
        ->toBe([
            'p', 'br', 'strong', 'em', 'u', 'ul', 'ol', 'li',
            'h2', 'h3', 'h4', 'blockquote', 'a', 'img', 'figure', 'figcaption',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
        ]);
});

test('the allowlist grants an element no attributes it did not name', function () {
    $elements = (array) config('html.elements');

    expect($elements['a'])->toBe(['href', 'target', 'rel'])
        ->and($elements['img'])->toBe(['src', 'alt'])
        ->and($elements['p'])->toBe([]);
});

test('the sanitizer blocks unknown tags instead of dropping them', function () {
    // Symfony's own default is drop, which would delete the contents of every
    // tag the allowlist does not mention. config/html.php has to say block.
    expect(config('html.default_action'))->toBe('block');
});

test('the media schemes exclude the data url default', function () {
    // Symfony allows 'data' in media schemes out of the box. Nothing here needs
    // one, and a data URL in an image src is a document in disguise.
    expect((array) config('html.media_schemes'))->toBe(['http', 'https'])
        ->and((array) config('html.media_schemes'))->not->toContain('data');
});
