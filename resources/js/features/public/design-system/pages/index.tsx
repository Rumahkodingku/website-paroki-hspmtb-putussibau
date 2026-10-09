import { ImageOff, Newspaper } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { EmptyState } from '@/components/empty-state';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import { ParishCta } from '@/components/parish-cta';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
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
                            <p className="text-caption-strong text-muted-foreground">
                                [ISI: label]
                            </p>
                            <p className="text-body-strong text-foreground">
                                [ISI: kartu default]
                            </p>
                            <p className="text-fine-print text-muted-foreground">
                                [ISI: permukaan canvas dengan garis hairline.]
                            </p>
                        </ParishCardContent>
                    </ParishCard>

                    <ParishCard variant="agenda">
                        <ParishCardContent className="flex flex-col gap-2">
                            <p className="text-caption-strong text-navy">
                                [ISI: label]
                            </p>
                            <p className="text-body-strong text-foreground">
                                [ISI: kartu agenda]
                            </p>
                            <p className="text-fine-print text-ink-muted">
                                [ISI: permukaan navy-light, sesuai
                                components.agenda-card.]
                            </p>
                        </ParishCardContent>
                    </ParishCard>

                    <ParishCard interactive>
                        <ParishCardContent className="flex flex-col gap-2">
                            <p className="text-caption-strong text-muted-foreground">
                                [ISI: label]
                            </p>
                            <p className="text-body-strong text-foreground">
                                <LinkLike>[ISI: kartu interaktif]</LinkLike>
                            </p>
                            <p className="text-fine-print text-muted-foreground">
                                [ISI: punya border fokus, bukan hanya hover.]
                            </p>
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
        <span className="cursor-pointer underline underline-offset-4">
            {children}
        </span>
    );
}
