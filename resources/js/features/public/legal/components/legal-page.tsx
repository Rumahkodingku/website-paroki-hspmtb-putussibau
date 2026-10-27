import { Head } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { RichText } from '@/components/rich-text';
import PublicHeroBanner from '@/components/public-hero-banner';
import { PublicContainer } from '@/components/public-container';
import { home } from '@/routes';
import type { SharedProps } from '@/types';
import { usePage } from '@inertiajs/react';

type LegalPageProps = {
    title: string;
    eyebrow?: string;
    description?: string;
    content: string | null;
};

export function LegalPage({
    title,
    eyebrow = 'INFORMASI PAROKI',
    description,
    content,
}: LegalPageProps) {
    const { seo } = usePage<SharedProps>().props;

    return (
        <>
            <Seo
                title={title}
                description={description ?? seo.description ?? undefined}
            />

            <Head title={title} />

            <main>
                <PublicHeroBanner
                    eyebrow={eyebrow}
                    title={title}
                    description={description}
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title },
                    ]}
                />

                <section className="bg-background py-14 sm:py-16 lg:py-20">
                    <PublicContainer width="default">
                        {content ? (
                            <RichText html={content} className="max-w-3xl" />
                        ) : (
                            <Text as="p" variant="body" color="muted">
                                Konten belum tersedia.
                            </Text>
                        )}
                    </PublicContainer>
                </section>
            </main>
        </>
    );
}
