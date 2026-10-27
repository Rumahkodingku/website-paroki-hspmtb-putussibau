import { useState } from 'react';
import { Check, Copy } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function CopyButton({
    value,
    label,
    className,
}: {
    value: string;
    label: string;
    className?: string;
}) {
    const [copied, setCopied] = useState(false);

    async function copy(): Promise<void> {
        if (!navigator.clipboard) {
            return;
        }

        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            // Permission denied or an insecure context: nothing to announce.
        }
    }

    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={copy}
            aria-label={label}
            title={label}
            className={className}
        >
            {copied ? (
                <Check aria-hidden="true" />
            ) : (
                <Copy aria-hidden="true" />
            )}
            <span aria-live="polite">{copied ? 'Tersalin' : 'Salin'}</span>
        </Button>
    );
}

export default CopyButton;
