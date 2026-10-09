import { ImageOff, Newspaper } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { EmptyState } from '@/components/empty-state';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import { ParishCta } from '@/components/parish-cta';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { home } from '@/routes';
import { index as profil } from '@/routes/profil';

/*
 * /design-system - the shell showcase.
 *
 * Roadmap section 12 asks for one reference page proving tokens, primitives,
 * shared components, layout, responsive behaviour and accessibility work
 * together. It is explicitly NOT the homepage, and PRD 13 Fase 4 owns the real
 * one.
 *
 * Nothing here is parish content. The copy is "Design System Preview" or
 * [ISI: ...], and the imagery is a neutral placeholder block rather than
 * invented parish photography - AGENTS.md forbids inventing clergy, schedules and
 * addresses, and a stock-looking photo of a church would be inventing the visual
 * identity of a real parish.
 *
 * The route is not in PRD 5.1. D-31 records that, and the page is noindex so it
 * does not compete with anything real for a search result.
 *
 * Every block below is something a Phase 02+ module will actually use, which is
 * what makes this a test of the foundation rather than a gallery: if a module
 * cannot build its page from what is here, the foundation is missing something.
 */

export default function DesignSystem() {
    return (
        <>
            <Seo
                title="Pratinjau Design System"
                description="[ISI: pratinjau internal shell publik. Bukan konten situs.]"
                noIndex
            />

            <PublicSection size="large">
                <SectionHeading
                    eyebrow="Design System Preview"
                    title="HSPMTB Design System"
                    description="[ISI: pratinjau internal. Halaman ini membuktikan token, primitive, komponen bersama, layout, perilaku responsif, dan aksesibilitas bekerja bersama. Bukan konten produksi.]"
                />
            </PublicSection>

            {/* Breadcrumbs: the shape a Profil sub-page or a news article uses. */}
            <PublicSection size="default">
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Profil', href: profil() },
                        { title: 'Pratinjau Design System' },
                    ]}
                />
            </PublicSection>

            {/* Cards: default and agenda surfaces, plus the interactive case. */}
            <PublicSection>
                <SectionHeading
                    eyebrow="Kartu"
                    title="ParishCard"
                    description="[ISI: contoh tiga kartu. Varian default, agenda, dan interactive untuk kartu yang menjadi tautan.]"
                />

                <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <ParishCard>
                        <ParishCardContent className="flex flex-col gap-2">
                            <Text as="p" variant="caption-strong" color="muted">
                                [ISI: label]
                            </Text>
                            <Text
                                as="p"
                                variant="body-strong"
                                color="foreground"
                            >
                                [ISI: kartu default]
                            </Text>
                            <Text as="p" variant="fine-print" color="muted">
                                [ISI: permukaan canvas dengan garis hairline.]
                            </Text>
                        </ParishCardContent>
                    </ParishCard>

                    <ParishCard variant="agenda">
                        <ParishCardContent className="flex flex-col gap-2">
                            {/*
                                brand rather than a literal text-navy: this is
                                the navy-as-text case, and the variant carries
                                the dark override with it so this label stays
                                legible when the agenda surface turns dark.
                            */}
                            <Text as="p" variant="caption-strong" color="brand">
                                [ISI: label]
                            </Text>
                            <Text
                                as="p"
                                variant="body-strong"
                                color="foreground"
                            >
                                [ISI: kartu agenda]
                            </Text>
                            <Text
                                as="p"
                                variant="fine-print"
                                className="text-ink-muted"
                            >
                                [ISI: permukaan navy-light, sesuai
                                components.agenda-card.]
                            </Text>
                        </ParishCardContent>
                    </ParishCard>

                    <ParishCard interactive>
                        <ParishCardContent className="flex flex-col gap-2">
                            <Text as="p" variant="caption-strong" color="muted">
                                [ISI: label]
                            </Text>
                            <Text
                                as="p"
                                variant="body-strong"
                                color="foreground"
                            >
                                <LinkLike>[ISI: kartu interaktif]</LinkLike>
                            </Text>
                            <Text as="p" variant="fine-print" color="muted">
                                [ISI: punya border fokus, bukan hanya hover.]
                            </Text>
                        </ParishCardContent>
                    </ParishCard>
                </div>
            </PublicSection>

            {/* Buttons: every variant the document lists, on one row. */}
            <PublicSection surface="soft">
                <SectionHeading
                    eyebrow="Tombol"
                    title="Button variants"
                    description="[ISI: primary, secondary, navy, gold, utility, dan ikon. Gold tidak pernah menjadi CTA utama.]"
                />

                <div className="mt-10 flex flex-wrap items-center gap-3">
                    <Button size="lg">Jadwal Misa</Button>
                    <Button size="lg" variant="outline">
                        Hubungi Kami
                    </Button>
                    <Button size="lg" variant="secondary">
                        Daftar Pelayanan
                    </Button>
                    <Button size="lg" variant="gold">
                        [ISI: aksi seremonial]
                    </Button>
                    <Button size="sm" variant="ghost">
                        [ISI: utilitas]
                    </Button>
                    <Button
                        size="icon"
                        variant="outline"
                        aria-label="Ikon 44px"
                    >
                        <ImageOff />
                    </Button>
                </div>
            </PublicSection>

            {/* Empty state: the generic one, still feature-free. */}
            <PublicSection>
                <SectionHeading
                    eyebrow="Kosong"
                    title="EmptyState"
                    description="[ISI: satu komponen untuk empat daftar kosong: berita, agenda, album, dokumen. Isinya decided oleh pemanggil.]"
                />

                <div className="mt-10 rounded-lg border border-hairline bg-card">
                    <EmptyState
                        icon={Newspaper}
                        title="[ISI: belum ada berita terbaru]"
                        description="[ISI: berita yang terbit akan muncul di sini.]"
                    />
                </div>
            </PublicSection>

            {/* Skeletons: the four patterns, used inline rather than abstracted. */}
            <PublicSection surface="soft">
                <SectionHeading
                    eyebrow="Memuat"
                    title="Skeleton"
                    description="[ISI: pola kartu, judul, gambar, dan daftar. Dipakai inline; tidak dijadikan komponen sendiri.]"
                />

                <div className="mt-10 grid gap-6 lg:grid-cols-2">
                    <div className="flex flex-col gap-3 rounded-lg border border-hairline bg-card p-6">
                        <Skeleton className="h-5 w-1/3" />
                        <Skeleton className="h-40 w-full rounded-md" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-2/3" />
                    </div>

                    <div className="flex flex-col gap-3 rounded-lg border border-hairline bg-card p-6">
                        <Skeleton className="h-6 w-2/3" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-5/6" />
                        <Skeleton className="h-4 w-1/2" />
                        <div className="flex gap-3 pt-2">
                            <Skeleton className="size-10 rounded-full" />
                            <div className="flex-1 space-y-2">
                                <Skeleton className="h-4 w-1/2" />
                                <Skeleton className="h-3 w-1/3" />
                            </div>
                        </div>
                    </div>
                </div>
            </PublicSection>

            {/*
                CTA: navy surface, white type, one red primary and at most one
                outline alternative. DESIGN.md says this twice and means it.
            */}
            <PublicSection>
                <ParishCta
                    title="[ISI: judul ajakan bertindak]"
                    description="[ISI: satu kalimat yang menjelaskan langkah berikutnya. Tetap satu aksi utama, tidak menjadi deretan tombol.]"
                    level={2}
                >
                    <Button size="lg">Jadwal Misa</Button>
                </ParishCta>
            </PublicSection>

            <PublicSection>
                <ParishCta
                    title="[ISI: CTA dengan aksi sekunder]"
                    description="[ISI: varian dengan satu alternatif, tetap berjarak jelas dari aksi utama.]"
                    secondaryLabel="Hubungi Kami"
                    align="center"
                    level={2}
                >
                    <Button size="lg">Lihat Selengkapnya</Button>
                </ParishCta>
            </PublicSection>

            {/*
                Typography.
                ----------
                Text is the component every other piece of text on the public
                site goes through, so it belongs here rather than in a second
                page. The examples are deliberately the ones a caller gets
                wrong: the display steps next to the heading hierarchy, a
                semantic colour that changes in dark mode, the truncation and
                clamping that a long Indonesian parish name will trigger, and
                `as` doing its job without changing the variant.
            */}
            <PublicSection size="large">
                <SectionHeading
                    eyebrow="Tipografi"
                    title="Text"
                    description="[ISI: enam belas token typography, pemetaan variant ke elemen semantik, warna semantik, perataan, pemenggalan kata, dan pemotongan baris. Semua contoh memakai teks placeholder, bukan data paroki.]"
                />

                <div className="mt-10 flex flex-col gap-12">
                    {/* Display steps and the heading hierarchy. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            Display dan hierarki heading
                        </Text>

                        <div className="mt-4 flex flex-col gap-4 border-l-4 border-hairline ps-4">
                            {/*
                                Every one of these is a real heading at a real
                                level. A screen reader stepping through this
                                page finds h1, h2, h3, h4 in order - which is the
                                property the variant/as split exists to keep.
                            */}
                            <Text as="h2" variant="display-lg">
                                display-lg / h1 — 40px, membawa text-balance
                            </Text>
                            <Text as="h3" variant="display-md">
                                display-md / h2 — 34px, membawa text-balance
                            </Text>
                            <Text as="h4" variant="tagline">
                                tagline / h3 — 21px
                            </Text>
                            <Text as="h5" variant="body-strong">
                                body-strong / h4 — 17px
                            </Text>
                            <Text as="h6" variant="caption-strong">
                                caption-strong / h5, h6 — 14px
                            </Text>
                            <Text as="p" variant="hero">
                                hero — 56px, khusus hero beranda
                            </Text>
                        </div>
                    </div>

                    {/* Body, lead, caption and the small print. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            Body, lead, caption, dan fine print
                        </Text>

                        <div className="mt-4 flex flex-col gap-3">
                            <Text as="p" variant="lead">
                                lead — 28px, teks pendukung hero.
                            </Text>
                            <Text as="p" variant="lead-airy">
                                lead-airy — 24px, lead editorial yang longgar.
                            </Text>
                            <Text as="p" variant="body">
                                body — 17px, ukuran bacaan baku. Ini yang
                                dipakai paragraf mana pun yang tidak punya peran
                                khusus.
                            </Text>
                            <Text as="p" variant="body-strong">
                                body-strong — 17px, penekanan di dalam teks.
                            </Text>
                            <Text as="p" variant="caption">
                                caption — 14px, metadata dan label sekunder.
                            </Text>
                            <Text as="p" variant="caption-strong">
                                caption-strong — 14px, judul kecil.
                            </Text>
                            <Text as="p" variant="nav">
                                nav — 13px, link navigasi utama.
                            </Text>
                            <Text as="p" variant="fine-print">
                                fine-print — 12px, footer dan legal.
                            </Text>
                            <Text as="p" variant="micro-legal">
                                micro-legal — 10px, microcopy legal yang jarang.
                            </Text>
                        </div>
                    </div>

                    {/* Semantic colours. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            Warna semantik
                        </Text>

                        <div className="mt-4 grid gap-2 sm:grid-cols-2">
                            {/*
                                `brand` is the one worth switching themes on
                                to see: text-navy measures 1.25:1 on the dark
                                canvas, so the variant carries its own dark
                                override. That is why `success` and `warning`
                                are absent from this list - DESIGN.md has no
                                green and no amber.
                            */}
                            <Text as="p" color="foreground">
                                foreground — teks utama
                            </Text>
                            <Text as="p" color="muted">
                                muted — deskripsi dan metadata
                            </Text>
                            <Text as="p" color="subtle">
                                subtle — ink-muted-soft, footer
                            </Text>
                            <Text as="p" color="brand">
                                brand — navy, berubah di dark mode
                            </Text>
                            <Text as="p" color="brand-inverse">
                                brand-inverse — navy-light, di atas navy
                            </Text>
                            <Text as="p" color="danger">
                                danger — red-on-surface, untuk pesan galat
                            </Text>
                            <Text as="p" color="inherit">
                                inherit — mengikuti induknya
                            </Text>
                        </div>
                    </div>

                    {/* Alignment. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            Perataan
                        </Text>

                        <div className="mt-4 flex flex-col gap-2">
                            {(
                                ['left', 'center', 'right', 'justify'] as const
                            ).map((align) => (
                                <Text
                                    key={align}
                                    as="p"
                                    variant="caption"
                                    align={align}
                                    className="border-b border-hairline pb-1"
                                >
                                    {align}
                                </Text>
                            ))}
                        </div>
                    </div>

                    {/* Transform and decoration. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            Transform dan dekorasi
                        </Text>

                        <div className="mt-4 flex flex-wrap items-center gap-4">
                            <Text
                                as="p"
                                variant="caption"
                                transform="uppercase"
                            >
                                uppercase
                            </Text>
                            <Text
                                as="p"
                                variant="caption"
                                transform="lowercase"
                            >
                                LOWERCASE
                            </Text>
                            <Text
                                as="p"
                                variant="caption"
                                transform="capitalize"
                            >
                                capitalize
                            </Text>
                            <Text
                                as="p"
                                variant="caption"
                                decoration="underline"
                            >
                                underline
                            </Text>
                            <Text
                                as="p"
                                variant="caption"
                                decoration="line-through"
                            >
                                line-through
                            </Text>
                            <Text as="p" variant="caption" italic>
                                italic
                            </Text>
                        </div>
                    </div>

                    {/* Truncation, clamping and word breaking. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            Pemotongan baris dan pemenggalan kata
                        </Text>

                        <div className="mt-4 flex flex-col gap-3">
                            {/*
                                A long one so the single-line truncation is
                                visibly doing something. The container is
                                bounded on purpose: truncate only works if the
                                flex or grid parent can actually shrink, which
                                is the other half of the lesson and the reason
                                breadcrumbs.tsx pairs it with min-w-0.
                            */}
                            <div className="max-w-xs">
                                <Text as="p" variant="body-strong" truncate>
                                    [ISI: nama paroki panjang yang dipotong satu
                                    baris]
                                </Text>
                            </div>

                            <div className="max-w-sm">
                                <Text as="p" variant="body" lineClamp={2}>
                                    [ISI: paragraf yang dipotong dua baris.
                                    lineClamp menang bila truncate diberikan
                                    bersamaan.]
                                </Text>
                            </div>

                            <div className="max-w-xs">
                                <Text as="p" variant="caption" break="words">
                                    [ISI]:
                                    nama-paroki-yang-sangat-panjang-sehingga-perlu-dipenggalan-di-tengah-kata
                                </Text>
                            </div>
                        </div>
                    </div>

                    {/* Semantic elements chosen by `as`. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            Elemen semantik lewat as
                        </Text>

                        <div className="mt-4 flex flex-col gap-3">
                            {/*
                                All six share variant="body-strong", and all six
                                prove the separation: `as` picks the tag,
                                `variant` picks the typography, and neither one
                                reaches into the other.
                            */}
                            <Text as="p" variant="body-strong">
                                as="p"
                            </Text>
                            <Text as="span" variant="body-strong">
                                as="span" — di dalam paragraf ini.
                            </Text>
                            <Text as="strong" variant="body-strong">
                                as="strong"
                            </Text>
                            <Text as="em" variant="body-strong">
                                as="em"
                            </Text>
                            <Text as="small" variant="body-strong">
                                as="small"
                            </Text>
                            <Text
                                as="time"
                                variant="body-strong"
                                dateTime="2026-01-01"
                            >
                                as="time"
                            </Text>
                            <Text as="mark" variant="body-strong">
                                as="mark"
                            </Text>
                            <Text as="del" variant="body-strong">
                                as="del"
                            </Text>
                            <Text as="ins" variant="body-strong">
                                as="ins"
                            </Text>
                            <Text as="cite" variant="body-strong">
                                as="cite"
                            </Text>
                            <Text
                                as="label"
                                variant="body-strong"
                                htmlFor="contoh-label"
                            >
                                as="label"
                            </Text>
                            <input
                                id="contoh-label"
                                className="max-w-40 rounded-md border border-input px-2 py-1 text-sm"
                            />
                            <Text
                                as="kbd"
                                variant="body-strong"
                                className="rounded border border-hairline px-1"
                            >
                                as="kbd"
                            </Text>
                            <Text variant="blockquote">
                                blockquote — memakai perlakuan yang sama dengan
                                .rich-text blockquote di app.css.
                            </Text>
                        </div>
                    </div>

                    {/* className and the override props. */}
                    <div>
                        <Text as="p" variant="caption-strong" color="muted">
                            className dan override
                        </Text>

                        <div className="mt-4 flex flex-col gap-3">
                            {/*
                                The first two prove that className extends and
                                that a caller can override the variant: both are
                                the same 14px token, and the second one wins
                                because tailwind-merge now knows which group the
                                token belongs to.
                            */}
                            <Text
                                as="p"
                                variant="caption"
                                className="rounded-md bg-muted p-2"
                            >
                                className menambah tampilan, bukan mengganti
                                variant.
                            </Text>
                            <Text
                                as="p"
                                variant="caption"
                                className="text-micro-legal"
                            >
                                className="text-micro-legal" mengalahkan
                                variant.
                            </Text>

                            {/*
                                Overrides are constrained to utilities the design
                                system already allows. There is no `size` prop
                                accepting an arbitrary value, and weight stops
                                at semibold because DESIGN.md asks to avoid
                                700-heavy typography.
                            */}
                            <Text as="p" variant="body" weight="medium">
                                weight="medium"
                            </Text>
                            <Text as="p" variant="body" leading="relaxed">
                                leading="relaxed"
                            </Text>
                            <Text as="p" variant="body" tracking="wide">
                                tracking="wide"
                            </Text>
                            <Text as="p" variant="body" whitespace="nowrap">
                                whitespace="nowrap"
                            </Text>
                        </div>
                    </div>
                </div>
            </PublicSection>
        </>
    );
}

/*
 * A card body that behaves like a link without being one. Kept local to this
 * page on purpose: promoting it to a ParishCard variant is exactly the premature
 * abstraction roadmap section 21.5 warns about, because no real module needs it
 * until its own card arrives.
 */
function LinkLike({ children }: { children: string }) {
    return (
        <Text
            as="span"
            variant="body-strong"
            className="cursor-pointer underline underline-offset-4"
        >
            {children}
        </Text>
    );
}
