import { Link } from '@inertiajs/react';
import { ArrowUp } from 'lucide-react';
import { LEGAL } from '@/layouts/public/components/footer/footer-data';

export function FooterLegal() {
    return (
        <div className="flex flex-wrap items-center gap-x-3 gap-y-2 sm:gap-x-4">
            {LEGAL.map((item, index) => (
                <span
                    key={item.label}
                    className="inline-flex items-center gap-x-3 sm:gap-x-4"
                >
                    {index > 0 ? (
                        <span aria-hidden="true" className="text-subtle">
                            |
                        </span>
                    ) : null}
                    <Link
                        href={item.href}
                        className="inline-flex min-h-8 items-center text-fine-print text-muted-foreground transition-colors hover:text-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        {item.label}
                    </Link>
                </span>
            ))}
            <a
                href="#top"
                aria-label="Kembali ke atas"
                className="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-foreground transition-colors hover:bg-primary hover:text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <ArrowUp className="size-4" aria-hidden="true" />
            </a>
        </div>
    );
}
