import { RichText } from '@/components/rich-text';
import { Text } from '@/components/text';
import type { PublicPastor } from '../types';

export function PastorCard({ pastor }: { pastor: PublicPastor }) {
    const displayName = pastor.title
        ? `${pastor.title} ${pastor.name}`
        : pastor.name;

    return (
        <article className="flex flex-col gap-4 sm:flex-row sm:items-start">
            {pastor.photo ? (
                <img
                    src={pastor.photo}
                    alt={pastor.photo_alt ?? displayName}
                    loading="lazy"
                    className="aspect-4/5 w-full max-w-40 rounded-lg border border-border object-cover sm:w-40"
                />
            ) : null}

            <div className="space-y-2">
                <div>
                    <Text as="h3" variant="h3">
                        {displayName}
                    </Text>
                    {pastor.position_label ? (
                        <Text as="p" variant="label" color="danger">
                            {pastor.position_label}
                            {pastor.start_year
                                ? ` · sejak ${pastor.start_year}`
                                : ''}
                        </Text>
                    ) : null}
                </div>

                {pastor.bio ? <RichText html={pastor.bio} /> : null}
            </div>
        </article>
    );
}

export default PastorCard;
