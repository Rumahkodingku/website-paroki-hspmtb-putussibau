<?php

/*
|--------------------------------------------------------------------------
| HTML sanitization
|--------------------------------------------------------------------------
|
| The allowlist for every piece of rich text this application stores or renders:
| the privacy policy, article bodies, profile sections, descriptions.
|
| PRD D-14 makes server-side sanitization mandatory and calls client-side
| sanitization insufficient on its own. That is the whole reason this file
| exists. Anything not listed here does not reach the browser.
|
| It lives in one file on purpose, for the same reason config/media.php does.
| The service builds the Symfony config from this, and a second copy of a tag
| list in a service constructor is a list that eventually disagrees with the
| one the tests read.
|
| @see docs/DECISIONS.md D-25
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed elements
    |--------------------------------------------------------------------------
    |
    | PRD section 22 lists the minimum: p, br, strong, em, u, ul, ol, li, h2,
    | h3, h4, blockquote, a, img, figure, figcaption, table. XC-S1 repeats it and
    | shows the attribute expectations: a[href|target|rel], img[src|alt].
    |
    | The table descendants are not in that list but have to be, because a bare
    | <table> renders nothing. Browsers synthesise a tbody around loose rows, but
    | relying on that means the admin sees something in the editor and something
    | different after a round trip.
    |
    | Note what is absent. There is no class, no id and no style, so pasted
    | Word or Google Docs markup cannot smuggle layout or CSS into the page, and
    | there is no <code>, <pre>, <s> or <hr> because the PRD does not list them.
    | The editor is configured to match this file; see
    | resources/js/components/rich-text-editor.tsx.
    |
    | img deliberately allows only src and alt. Width and height are how you
    | avoid layout shift, but they are also how markup claims a size it does not
    | have, and the media module already knows the real dimensions of every
    | image it publishes.
    |
    */

    'elements' => [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'u' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'blockquote' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt'],
        'figure' => [],
        'figcaption' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'],
        'td' => ['colspan', 'rowspan'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dropped elements
    |--------------------------------------------------------------------------
    |
    | These are removed together with everything inside them. That is the
    | difference between drop and block in Symfony's sanitizer: blocking a
    | <script> would leave alert(1) sitting in the document as visible text,
    | which is still not an execution hole but is still not what the author
    | wrote and is a confusing thing to find on a parish website.
    |
    | svg is here because it is a document format that can carry script, and
    | the same goes for the form family. Dropping them keeps the sanitizer from
    | being asked a question about vectors it does not need to answer.
    |
    */

    'drop_elements' => [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'applet',
        'svg',
        'math',
        'form',
        'input',
        'button',
        'select',
        'option',
        'textarea',
        'noscript',
        'template',
        'meta',
        'link',
        'base',
        'frame',
        'frameset',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unknown elements
    |--------------------------------------------------------------------------
    |
    | block, not drop. Anything the allowlist does not mention loses its tag and
    | keeps its text, so a <div> wrapper or a stray <span> from pasted content
    | costs the author their formatting but never their words.
    |
    | Symfony defaults to drop, which would delete the contents of every
    | unknown tag. That is the wrong trade for a CMS: an admin pasting a
    | paragraph out of a document should not lose the paragraph because the
    | source document wrapped it in something we do not allow.
    |
    | Anything genuinely dangerous is listed under drop_elements above instead,
    | so this setting never decides the fate of a script.
    |
    */

    'default_action' => 'block',

    /*
    |--------------------------------------------------------------------------
    | URL schemes
    |--------------------------------------------------------------------------
    |
    | Link schemes match Symfony's defaults. mailto and tel are there because
    | the parish publishes a phone number and an office address, and SVC
    | settings do the same.
    |
    | Media schemes are tightened. Symfony allows 'data' by default, and a
    | data: URL in an image src can carry a whole document. Nothing in this
    | application needs one: published images live under /storage, and the
    | originals are private and reached through the media endpoints.
    |
    */

    'link_schemes' => ['http', 'https', 'mailto', 'tel'],

    'media_schemes' => ['http', 'https'],

    /*
    |--------------------------------------------------------------------------
    | Relative URLs
    |--------------------------------------------------------------------------
    |
    | Allowed for both. A published image variant is referenced as
    | /storage/media/variants/..., which has no scheme and no host, and it has
    | to survive the sanitizer or every image in the application is broken by
    | the privacy policy being saved.
    |
    */

    'allow_relative_links' => true,

    'allow_relative_media' => true,

    /*
    |--------------------------------------------------------------------------
    | External links
    |--------------------------------------------------------------------------
    |
    | XC-S1 requires rel="noopener noreferrer" on external links. It is forced
    | onto every <a> rather than added conditionally, because the alternative is
    | deciding what counts as external from a string the editor produced, and
    | rel is harmless on an internal link.
    |
    */

    'link_rel' => 'noopener noreferrer',

    /*
    |--------------------------------------------------------------------------
    | Input ceiling
    |--------------------------------------------------------------------------
    |
    | A guard, not the real limit. The privacy policy is capped separately by
    | its validation rule; this stops a pathological paste from reaching the
    | parser at all.
    |
    */

    'max_input_length' => 500000,

];
