import { toast } from 'sonner';
import { Text } from '@/components/text';

/**
 * Share an article over WhatsApp, Facebook, or the clipboard.
 *
 * PRD 7.4 asks for all three. The URL is the page's own — read from the window
 * rather than rebuilt from a stored slug — so a link that includes a filter or
 * a share is what gets shared, which is the whole point of the filters living
 * in the query string.
 */
export function ShareActions({ url, title }: { url: string; title: string }) {
    /**
     * Copy, with a fallback.
     *
     * navigator.clipboard is undefined on an insecure origin, and a parish site
     * is very likely to be opened over plain HTTP on a shared machine at some
     * point. Doing nothing there would leave the button looking broken.
     */
    const copyLink = async () => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
            } else {
                const field = document.createElement('textarea');
                field.value = url;
                field.setAttribute('readonly', '');
                field.style.position = 'absolute';
                field.style.left = '-9999px';
                document.body.appendChild(field);
                field.select();
                const copied = document.execCommand('copy');
                document.body.removeChild(field);

                if (!copied) {
                    throw new Error('execCommand refused');
                }
            }

            toast.success('Tautan artikel disalin.');
        } catch {
            toast.error('Tautan tidak dapat disalin. Salin dari bilah alamat.');
        }
    };

    return (
        <div
            className="flex flex-wrap items-center gap-3"
            data-test="share-actions"
        >
            <Text as="p" variant="caption-strong" color="muted">
                Bagikan
            </Text>

            <a
                href={`https://wa.me/?text=${encodeURIComponent(`${title} ${url}`)}`}
                target="_blank"
                rel="noopener noreferrer"
                data-test="share-whatsapp"
                className="inline-flex h-11 items-center rounded-md border border-hairline px-4 text-sm hover:border-primary focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
            >
                WhatsApp
            </a>

            <a
                href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`}
                target="_blank"
                rel="noopener noreferrer"
                data-test="share-facebook"
                className="inline-flex h-11 items-center rounded-md border border-hairline px-4 text-sm hover:border-primary focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
            >
                Facebook
            </a>

            <button
                type="button"
                onClick={copyLink}
                data-test="share-copy"
                className="inline-flex h-11 items-center rounded-md border border-hairline px-4 text-sm hover:border-primary focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
            >
                Salin tautan
            </button>
        </div>
    );
}
