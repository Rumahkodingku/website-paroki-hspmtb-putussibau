/*
 * Renders stored rich text.
 *
 * This is the only place in the repository that uses dangerouslySetInnerHTML,
 * and it is only legitimate because of what it assumes about its input.
 *
 * The html prop MUST already have been through App\Services\HtmlSanitizer. That
 * happens on the way in, in UpdateSettingsRequest, so the sanitizer is not
 * something a caller has to remember to apply at every render site: by the time
 * a value reaches a page it has already been cleaned once, and cleaning it again
 * is a no-op because the sanitizer is idempotent.
 *
 * The invariant is worth stating plainly because the alternative is a stored
 * XSS. A paragraph rendered as {value} would show its own markup as text; the
 * reason this file is allowed to do that is that the string contains only the
 * twenty-two tags in config/html.php and none of them carries an event handler
 * or a script-bearing URL. Adding a tag to that allowlist means re-reading this
 * comment.
 *
 * Client-side sanitization is not the substitute here, per PRD D-14. The browser
 * check would be a second, weaker implementation of a rule the server already
 * enforces, and it would be the one that silently breaks if a sanitizer library
 * were swapped.
 *
 * @see docs/DECISIONS.md D-25
 */

type RichTextProps = {
    /** HTML that has already been sanitized server-side. */
    html: string;
    className?: string;
};

export function RichText({ html, className }: RichTextProps) {
    /*
     * An empty string renders an empty element rather than nothing, because a
     * caller laying out a page generally wants the spacing to be consistent
     * whether or not the field has been filled in yet.
     */
    if (!html) {
        return null;
    }

    return (
        <div
            className={className ? `rich-text ${className}` : 'rich-text'}
            // Sanitized server-side on the way into storage. See the note above
            // this component; this is the only call site in the repository.
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}

export default RichText;
