import { ChevronDown } from 'lucide-react';
import { Text } from '@/components/text';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { PublicServiceFaq } from '../types';

export function FaqList({ items }: { items: PublicServiceFaq[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="divide-y divide-border">
            {items.map((item) => (
                <Collapsible key={item.id} className="group px-5 py-4">
                    <CollapsibleTrigger className="flex w-full cursor-pointer list-none items-center justify-between gap-4 text-left focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">
                        <Text as="span" variant="heading-sm">
                            {item.question}
                        </Text>

                        <ChevronDown
                            className="size-5 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                            aria-hidden="true"
                        />
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="mt-3 max-w-3xl"
                        >
                            {item.answer}
                        </Text>
                    </CollapsibleContent>
                </Collapsible>
            ))}
        </div>
    );
}
