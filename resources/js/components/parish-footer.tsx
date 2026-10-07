import { Link } from '@inertiajs/react';
import {
    type LucideIcon,
    CalendarDays,
    Church,
    Download,
    Mail,
    MapPin,
    Phone,
} from 'lucide-react';

/*
 * ParishFooter - DESIGN.md, components.footer
 *
 * Soft-neutral surface, ink-muted-80 text, 12px fine print. The document allows
 * this to be denser than the main navigation because its job is to expose the
 * site's information architecture at a glance.
 *
 * Nothing here is real parish data. Every value is a [ISI: ...] placeholder, per
 * the standing rule in AGENTS.md and the Don'ts section of DESIGN.md, which
 * forbids inventing clergy names, addresses and contact details.
 */

type FooterLink = { label: string; href?: string };
type FooterGroup = { title: string; links: FooterLink[] };

const MENU_UTAMA: FooterGroup = {
    title: 'Menu Utama',
    links: [
        { label: 'Beranda', href: '/' },
        { label: 'Profil' },
        { label: 'Jadwal Misa' },
        { label: 'Berita & Artikel' },
        { label: 'Agenda' },
    ],
};

const PROFIL_PAROKI: FooterGroup = {
    title: 'Profil Paroki',
    links: [
        { label: 'Sejarah' },
        { label: 'Visi & Misi' },
        { label: 'Wilayah Pelayanan' },
        { label: 'Pastor' },
        { label: 'Struktur Kepengurusan' },
    ],
};

const PELAYANAN: FooterGroup = {
    title: 'Pelayanan & Komunitas',
    links: [
        { label: 'Baptis' },
        { label: 'Komuni Pertama' },
        { label: 'Penguatan' },
        { label: 'Pernikahan' },
        { label: 'Pengurapan Orang Sakit' },
        { label: 'Galeri', href: '#' },
        { label: 'Download', href: '#' },
    ],
};

function FooterLinks({ group }: { group: FooterGroup }) {
    return (
        <div>
            <h2 className="text-caption-strong text-foreground">
                {group.title}
            </h2>

            <ul className="mt-4 space-y-2">
                {group.links.map((link) => (
                    <li key={link.label}>
                        {link.href ? (
                            <Link
                                href={link.href}
                                className="inline-flex min-h-11 items-center text-fine-print text-ink-muted transition-colors hover:text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                            >
                                {link.label}
                            </Link>
                        ) : (
                            <span className="inline-flex min-h-11 items-center text-fine-print text-ink-muted-soft">
                                {link.label}
                            </span>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}

function ContactRow({
    icon: Icon,
    label,
    value,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
}) {
    return (
        <li className="flex items-start gap-2 text-fine-print text-ink-muted">
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>
                <span className="sr-only">{label}: </span>
                {value}
            </span>
        </li>
    );
}

export function ParishFooter() {
    return (
        <footer className="border-t border-divider-soft bg-canvas-soft">
            <div className="mx-auto w-full max-w-7xl px-4 py-16">
                <div className="grid gap-12 md:grid-cols-2 lg:grid-cols-5">
                    <div className="lg:col-span-2">
                        <div className="flex items-center gap-2">
                            <Church
                                className="size-5 text-primary"
                                aria-hidden="true"
                            />
                            <span className="text-body-strong text-foreground">
                                HSPMTB Putussibau
                            </span>
                        </div>

                        <p className="mt-4 max-w-sm text-fine-print text-ink-muted">
                            [ISI: deskripsi singkat paroki. Satu atau dua
                            kalimat yang menjelaskan siapa kami.]
                        </p>

                        <ul className="mt-6 space-y-3">
                            <ContactRow
                                icon={MapPin}
                                label="Alamat"
                                value="[ISI: alamat lengkap paroki]"
                            />
                            <ContactRow
                                icon={Phone}
                                label="Telepon"
                                value="[ISI: nomor sekretariat]"
                            />
                            <ContactRow
                                icon={Mail}
                                label="Surel"
                                value="[ISI: alamat surel paroki]"
                            />
                            <ContactRow
                                icon={CalendarDays}
                                label="Misa"
                                value="[ISI: jadwal misa]"
                            />
                            <ContactRow
                                icon={Download}
                                label="Unduhan"
                                value="[ISI: tautan dokumen resmi]"
                            />
                        </ul>
                    </div>

                    <FooterLinks group={MENU_UTAMA} />
                    <FooterLinks group={PROFIL_PAROKI} />
                    <FooterLinks group={PELAYANAN} />
                </div>

                <div className="mt-12 flex flex-col gap-4 border-t border-divider-soft pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-micro-legal text-ink-muted-soft">
                        &copy; {new Date().getFullYear()} [ISI: nama resmi
                        paroki]. Seluruh hak cipta dilindungi.
                    </p>

                    <ul className="flex flex-wrap gap-x-6 gap-y-2">
                        {[
                            'Kebijakan Privasi',
                            'Syarat & Ketentuan',
                            'Kontak',
                        ].map((label) => (
                            <li key={label}>
                                <span className="text-micro-legal text-ink-muted-soft">
                                    {label}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </footer>
    );
}
