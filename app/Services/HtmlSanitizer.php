<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans rich text before it is stored, and before it is rendered.
 *
 * PRD D-14 makes this mandatory and rules out client-side sanitization as a
 * substitute: the editor is a convenience, the server is the boundary. Anything
 * that ends up in the database as an HTML fragment goes through clean() on the
 * way in.
 *
 * The allowlist lives in config/html.php. Symfony does the parsing, the walking
 * and the attribute filtering, because a hand-written sanitizer is the kind of
 * code that looks obviously correct and is wrong in a way nobody notices until
 * it is exploited. What is left here is the part Symfony cannot decide: whether
 * an unknown tag costs its text or costs everything, and what happens to the
 * presentational tags the allowlist has no name for.
 *
 * @see docs/DECISIONS.md D-25
 */
class HtmlSanitizer
{
    /**
     * The configured Symfony sanitizer.
     *
     * Built once per instance. The class is registered as a singleton so the
     * attribute maps are parsed a single time for the life of the request
     * instead of once per field being saved.
     */
    private ?SymfonyHtmlSanitizer $sanitizer = null;

    /**
     * Return the HTML with everything outside the allowlist removed.
     *
     * Never returns null and never returns the input unchanged unless the input
     * already was clean, which makes it safe to call on a value that might be
     * absent: an unset privacy policy is an empty string, not a null column
     * written into a LONGTEXT that every reader then has to guard.
     */
    public function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return $this->sanitizer()->sanitize($this->normalise($html));
    }

    /**
     * Reduce sanitized HTML to readable plain text.
     *
     * For the places that need prose rather than markup: a meta description, an
     * Open Graph tag, an excerpt in a list. Running clean() first means the text
     * extracted here cannot contain a tag that was never allowed to exist.
     */
    public function toPlainText(?string $html): string
    {
        $text = $this->blockElementsToSpaces($this->clean($html));

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Replace block-level tags with a space before the inline tags are stripped.
     *
     * strip_tags() deletes a tag and nothing else, so two paragraphs separated by
     * </p><p> come out as one run: "duniaBaris baru". A meta description or an
     * excerpt is a single line, and one missing space there is a visible defect, so
     * the word break is inserted while the tags are still visible to be matched.
     *
     * Inline tags such as strong and em are deliberately absent. Those are word
     * boundaries already.
     */
    private function blockElementsToSpaces(string $html): string
    {
        return (string) preg_replace(
            '#<\/?(p|br|div|h[1-6]|ul|ol|li|blockquote|figure|figcaption|table|thead|tbody|tr|td|th)\b[^>]*>#i',
            ' ',
            $html,
        );
    }

    /**
     * Whether the given HTML would come back from clean() unchanged.
     *
     * Exists so a caller can assert a value is already safe, which is how a
     * test proves sanitization is idempotent. Nothing in the application should
     * branch on it.
     */
    public function isClean(?string $html): bool
    {
        return $this->clean($html) === (string) $html;
    }

    /**
     * Rewrite the presentational tags the allowlist does not have names for.
     *
     * Symfony's answer to a tag it does not allow is to block it: remove the tag
     * and keep the text. That is the right call for <div> and the wrong call for
     * <b>, because "k_enteng" and "miring" would arrive as plain unformatted
     * words. Pasted content is full of <b> and <i>, so without this the author
     * loses their emphasis every time they paste from Word or from a website.
     *
     * <h1> becomes <h2> for a different reason. The allowlist has h2, h3 and h4
     * and no h1, because h1 is the page title and belongs to the layout rather
     * than to a field an admin fills in. Blocking an h1 would leave its sentence
     * behind as loose body text, quietly destroying the author's structure, so
     * it is promoted instead. The result is a document with exactly one h1 and
     * it is not in the field.
     *
     * Only tag names are touched; attributes ride along and are then filtered by
     * the allowlist as usual, so none of this can smuggle anything past the
     * sanitizer.
     *
     * Escaped input is unaffected. &lt;b&gt; contains no literal <b, so it is
     * left alone and rendered as visible text.
     *
     * The patterns require a > or whitespace directly after the letter, which is
     * what keeps <b> from matching <blockquote>, <br> or <body>.
     */
    private function normalise(string $html): string
    {
        $html = $this->stripRawTextElements($html);

        $html = (string) preg_replace('/<\s*h1(\s[^>]*)?>/i', '<h2$1>', $html);
        $html = (string) preg_replace('/<\s*\/\s*h1\s*>/i', '</h2>', $html);

        $html = (string) preg_replace('/<\s*b(\s[^>]*)?>/i', '<strong$1>', $html);
        $html = (string) preg_replace('/<\s*\/\s*b\s*>/i', '</strong>', $html);

        $html = (string) preg_replace('/<\s*i(\s[^>]*)?>/i', '<em$1>', $html);

        return (string) preg_replace('/<\s*\/\s*i\s*>/i', '</em>', $html);
    }

    /**
     * Delete raw-text elements together with their contents before parsing.
     *
     * Elements such as <style> and <title> do not have a markup content model: an
     * HTML parser reads everything up to the matching close tag as one run of text.
     * Dropping the element therefore does not drop its content, and a pasted
     * stylesheet ends up in the sanitized output as the visible string
     * "p{color:red}" sitting in the middle of the article. <script> behaves the same
     * way in principle even though its body currently does not survive.
     *
     * It is inert rather than dangerous, because what is left is text and not a
     * tag. It is still a defect an administrator notices on day one, and a <title>
     * leaking the contents of someone's document is information disclosure rather
     * than cosmetics.
     *
     * The second pattern covers an unterminated opening tag, where the HTML spec
     * says the rest of the document is that element's content anyway.
     */
    private function stripRawTextElements(string $html): string
    {
        $html = (string) preg_replace(
            '#<\s*(script|style|title|textarea|noscript|iframe|xmp|noembed|noframes)\b[^>]*>.*?<\s*/\s*\1\s*>#is',
            '',
            $html,
        );

        return (string) preg_replace('#<\s*(script|style)\b[^>]*>.*$#is', '', $html);
    }

    /**
     * Build the Symfony sanitizer from config/html.php.
     */
    private function sanitizer(): SymfonyHtmlSanitizer
    {
        return $this->sanitizer ??= new SymfonyHtmlSanitizer($this->config());
    }

    /**
     * Translate the configuration into a Symfony sanitizer config.
     *
     * The elements are added explicitly rather than through allowSafeElements(),
     * because the point of the exercise is that nothing is allowed unless it was
     * decided in config/html.php.
     */
    private function config(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::from((string) config('html.default_action')))
            ->allowLinkSchemes($this->stringList('html.link_schemes'))
            ->allowMediaSchemes($this->stringList('html.media_schemes'))
            ->allowRelativeLinks((bool) config('html.allow_relative_links'))
            ->allowRelativeMedias((bool) config('html.allow_relative_media'))
            ->withMaxInputLength((int) config('html.max_input_length'));

        $elements = (array) config('html.elements');

        foreach ($elements as $element => $attributes) {
            $config = $config->allowElement((string) $element, $this->stringList((string) $element, $attributes));
        }

        foreach ($this->stringList('html.drop_elements') as $element) {
            $config = $config->dropElement($element);
        }

        return $config->forceAttribute('a', 'rel', (string) config('html.link_rel'));
    }

    /**
     * Read a configured list of strings, guaranteeing the shape Symfony wants.
     *
     * Symfony types these arguments as list<string> rather than array, because it
     * indexes into them. config() hands back array<mixed, mixed> for a list in a
     * config file, so every value is cast and the keys are dropped rather than
     * the signature being loosened to accept whatever arrives.
     *
     * @param  array<mixed>|null  $values
     * @return list<string>
     */
    private function stringList(string $key, ?array $values = null): array
    {
        $values ??= (array) config($key);

        return array_values(array_map(strval(...), $values));
    }
}
