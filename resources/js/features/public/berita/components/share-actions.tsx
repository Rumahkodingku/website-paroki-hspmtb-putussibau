import { toast } from 'sonner';
import { FacebookIcon } from '@/components/icons/facebook-icon';
import { InstagramIcon } from '@/components/icons/instagram-icon';
import { LinkedInIcon } from '@/components/icons/linkedin';
import { WhatsAppIcon } from '@/components/icons/whatsapp-icon';
import { Link } from 'lucide-react';

export function ShareActions({ url, title }: { url: string; title: string }) {
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

    const linkedinUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(
        url,
    )}`;

    return (
        <div
            className="flex flex-wrap items-center gap-3"
            data-test="share-actions"
        >
            <a
                href={`https://wa.me/?text=${encodeURIComponent(`${title} ${url}`)}`}
                target="_blank"
                rel="noopener noreferrer"
                data-test="share-whatsapp"
                className="inline-flex h-11 items-center gap-2 rounded-md px-4 text-sm font-medium"
            >
                <WhatsAppIcon className="size-4" aria-hidden="true" />
                WhatsApp
            </a>

            <a
                href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`}
                target="_blank"
                rel="noopener noreferrer"
                data-test="share-facebook"
                className="inline-flex h-11 items-center gap-2 rounded-md px-4 text-sm font-semibold"
            >
                <FacebookIcon className="size-4" aria-hidden="true" />
                Facebook
            </a>

            <a
                href={linkedinUrl}
                target="_blank"
                rel="noopener noreferrer"
                data-test="share-linkedin"
                className="inline-flex h-11 items-center gap-2 rounded-md px-4 text-sm font-semibold"
            >
                <LinkedInIcon className="size-4" aria-hidden="true" />
                LinkedIn
            </a>

            <button
                type="button"
                onClick={() => void copyLink()}
                data-test="share-instagram"
                className="inline-flex h-11 items-center gap-2 rounded-md px-4 text-sm font-semibold"
            >
                <InstagramIcon className="size-4" aria-hidden="true" />
                Salin untuk Instagram
            </button>

            <button
                type="button"
                onClick={() => void copyLink()}
                data-test="share-copy"
                className="inline-flex h-11 items-center gap-2 rounded-md px-4 text-sm font-semibold"
            >
                <Link width={20} height={20} />
                Salin tautan
            </button>
        </div>
    );
}
