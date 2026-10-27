import { Link } from '@inertiajs/react';
import { ExternalLink, Plus } from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { create as createEvent } from '@/routes/events';
import { create as createPost } from '@/routes/posts';

export function DashboardHeader({ name }: { name: string | null }) {
    return (
        <section className="relative isolate overflow-hidden">
            {/* Hero content */}
            <div className="relative flex flex-col gap-5 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div className="min-w-0 space-y-2">
                    <Text
                        as="p"
                        variant="label"
                        color="muted"
                        className="text-xs font-semibold tracking-[0.12em] uppercase"
                    >
                        Panel Pengelola Paroki
                    </Text>

                    <Text
                        as="h2"
                        variant="heading-md"
                        className="text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                    >
                        {name ? `Selamat datang, ${name}` : 'Selamat datang'}
                    </Text>

                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="max-w-2xl leading-relaxed"
                    >
                        Kelola berita, agenda, jadwal Misa, dan informasi
                        pelayanan Paroki HSPMTB Putussibau dari satu tempat.
                    </Text>

                    {/* Primary actions */}
                    <div className="flex flex-col gap-2 pt-2 sm:flex-row sm:flex-wrap">
                        <Button asChild className="w-full text-sm sm:w-auto">
                            <Link href={createPost().url}>
                                <Plus aria-hidden="true" />
                                Tulis Berita
                            </Link>
                        </Button>

                        <Button
                            asChild
                            variant="outline"
                            className="w-full bg-background/80 text-sm sm:w-auto"
                        >
                            <Link href={createEvent().url}>
                                <Plus aria-hidden="true" />
                                Tambah Agenda
                            </Link>
                        </Button>

                        <Button
                            asChild
                            variant="outline"
                            className="w-full bg-background/80 text-sm sm:w-auto"
                        >
                            <a
                                href={home().url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink aria-hidden="true" />
                                Lihat Website
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
