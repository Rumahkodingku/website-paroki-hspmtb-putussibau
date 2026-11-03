import { Head, useForm } from '@inertiajs/react';
import { ChevronDown, ExternalLink } from 'lucide-react';
import { useEffect, useState } from 'react';
import PageSeoController from '@/actions/App/Http/Controllers/Seo/PageSeoController';
import { Field, FormInput, FormTextarea } from '@/components/form-fields';
import InputError from '@/components/input-error';
import { ListHeader } from '@/components/list-parts';
import { MediaUploadField } from '@/components/media-upload-field';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { dashboard } from '@/routes';
import { cn } from '@/lib/utils';
import type { PageSeoEntry, PageSeoProps } from '../types';

type PageSeoValue = {
    meta_title: string;
    meta_description: string;
    og_media_id: string;
};

function initialValue(page: PageSeoEntry): PageSeoValue {
    return {
        meta_title: page.meta_title ?? '',
        meta_description: page.meta_description ?? '',
        og_media_id: page.og_media_id ?? '',
    };
}

export default function PageSeo({ pages }: PageSeoProps) {
    const [openKey, setOpenKey] = useState<string | null>(null);
    const form = useForm<{ pages: Record<string, PageSeoValue> }>({
        pages: Object.fromEntries(
            pages.map((page) => [page.key, initialValue(page)]),
        ),
    });

    const update = (key: string, field: keyof PageSeoValue, value: string) => {
        form.setData('pages', {
            ...form.data.pages,
            [key]: {
                ...form.data.pages[key],
                [field]: value,
            },
        });
    };

    useEffect(() => {
        const firstErrorKey = Object.keys(form.errors).find((key) =>
            key.startsWith('pages.'),
        );

        if (!firstErrorKey) {
            return;
        }

        const pageKey = firstErrorKey.split('.')[1];
        const page = pages.find((entry) => entry.key === pageKey);

        if (page) {
            setOpenKey(page.key);
        }
    }, [form.errors, pages]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.put(PageSeoController.update().url);
    };

    return (
        <>
            <Head title="SEO Halaman Statis" />

            <form onSubmit={submit} className="space-y-6 p-6">
                <ListHeader
                    title="SEO Halaman Statis"
                    description="Atur judul, deskripsi, dan gambar pratinjau untuk halaman publik. Kosongkan untuk memakai default situs."
                />

                {form.errors.pages ? (
                    <InputError message={form.errors.pages} />
                ) : null}

                <div className="space-y-4">
                    {pages.map((page) => {
                        const value = form.data.pages[page.key];
                        const titleError =
                            form.errors[`pages.${page.key}.meta_title`];
                        const descriptionError =
                            form.errors[`pages.${page.key}.meta_description`];
                        const mediaError =
                            form.errors[`pages.${page.key}.og_media_id`];
                        const isOpen = openKey === page.key;

                        return (
                            <Card key={page.key}>
                                <Collapsible
                                    open={isOpen}
                                    onOpenChange={(open) =>
                                        setOpenKey(open ? page.key : null)
                                    }
                                >
                                    <CardHeader className="flex flex-row items-start justify-between gap-3">
                                        <CollapsibleTrigger asChild>
                                            <button
                                                type="button"
                                                className="flex min-w-0 flex-1 items-start gap-3 text-left focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                <div className="min-w-0 flex-1 space-y-1">
                                                    <CardTitle className="text-sm">
                                                        {page.label}
                                                    </CardTitle>
                                                </div>

                                                <ChevronDown
                                                    aria-hidden="true"
                                                    className={cn(
                                                        'mt-1 size-5 shrink-0 text-muted-foreground transition-transform',
                                                        isOpen && 'rotate-180',
                                                    )}
                                                />
                                            </button>
                                        </CollapsibleTrigger>

                                        <a
                                            href={page.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            Buka halaman
                                            <ExternalLink
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                        </a>
                                    </CardHeader>

                                    <CollapsibleContent>
                                        <CardContent className="grid gap-5">
                                            <div className="mt-5 flex flex-col gap-5">
                                                <Field
                                                    id={`seo-title-${page.key}`}
                                                    label="Judul SEO"
                                                    hint="Kosongkan untuk memakai judul default situs."
                                                    error={titleError}
                                                >
                                                    <FormInput
                                                        id={`seo-title-${page.key}`}
                                                        value={value.meta_title}
                                                        placeholder="Judul untuk hasil pencarian"
                                                        onChange={(next) =>
                                                            update(
                                                                page.key,
                                                                'meta_title',
                                                                next,
                                                            )
                                                        }
                                                    />
                                                </Field>

                                                <Field
                                                    id={`seo-description-${page.key}`}
                                                    label="Deskripsi SEO"
                                                    hint="Maksimal 500 karakter. Kosongkan untuk memakai deskripsi default situs."
                                                    error={descriptionError}
                                                >
                                                    <FormTextarea
                                                        id={`seo-description-${page.key}`}
                                                        value={
                                                            value.meta_description
                                                        }
                                                        rows={3}
                                                        placeholder="Ringkasan singkat halaman untuk mesin pencari"
                                                        onChange={(next) =>
                                                            update(
                                                                page.key,
                                                                'meta_description',
                                                                next,
                                                            )
                                                        }
                                                    />
                                                    <Text
                                                        as="p"
                                                        variant="fine-print"
                                                        color="muted"
                                                    >
                                                        {
                                                            value
                                                                .meta_description
                                                                .length
                                                        }
                                                        /500 karakter
                                                    </Text>
                                                </Field>

                                                <MediaUploadField
                                                    id={`seo-image-${page.key}`}
                                                    variant="image"
                                                    aspect={1.91}
                                                    label="Gambar pratinjau (OG)"
                                                    hint="Rasio 1.91:1, minimal 1200 × 630 piksel. Kosongkan untuk memakai gambar default."
                                                    emptyHint="Belum ada gambar pratinjau."
                                                    currentUrl={
                                                        page.og_image_url
                                                    }
                                                    fieldName={`pages.${page.key}.og_media_id`}
                                                    removable
                                                    onMediaChange={(mediaId) =>
                                                        update(
                                                            page.key,
                                                            'og_media_id',
                                                            mediaId === null
                                                                ? ''
                                                                : String(
                                                                      mediaId,
                                                                  ),
                                                        )
                                                    }
                                                />

                                                <InputError
                                                    message={mediaError}
                                                />
                                            </div>
                                        </CardContent>
                                    </CollapsibleContent>
                                </Collapsible>
                            </Card>
                        );
                    })}
                </div>

                <div className="flex justify-end">
                    <Button
                        type="submit"
                        className="text-sm"
                        disabled={form.processing}
                    >
                        Simpan SEO halaman
                    </Button>
                </div>
            </form>
        </>
    );
}

PageSeo.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'SEO Halaman' },
    ],
};
