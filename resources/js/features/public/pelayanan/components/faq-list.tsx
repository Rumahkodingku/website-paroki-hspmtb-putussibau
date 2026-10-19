import { ChevronDown } from 'lucide-react';
import { Text } from '@/components/text';
import type { PublicServiceFaq } from '../types';

/**
 * The FAQ accordion.
 *
 * Built on native <details>/<summary> rather than a Radix accordion: the project
 * does not depend on one, and adding a library to draw a disclosure is precisely
 * the dependency AGENTS.md says not to add without approval. Native details is
 * keyboard-operable and screen-reader-correct for free, which is exactly what a
 * FAQ needs.
 */
export function FaqList({ items }: { items: PublicServiceFaq[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="divide-y divide-border rounded-lg border border-border">
            {items.map((item) => (
                <details key={item.id} className="group px-5 py-4">
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">
                        <Text as="span" variant="heading-sm">
                            {item.question}
                        </Text>

                        <ChevronDown
                            className="size-5 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                            aria-hidden="true"
                        />
                    </summary>

                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="mt-3 max-w-3xl"
                    >
                        {item.answer}
                    </Text>
                </details>
            ))}
        </div>
    );
}
