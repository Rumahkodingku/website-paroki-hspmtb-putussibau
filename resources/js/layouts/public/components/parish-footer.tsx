import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import { type LucideIcon, Church, Mail, MapPin, Phone } from 'lucide-react';
import { PublicContainer } from '@/components/public-container';
import type { SeoDefaults } from '@/types';
import { home } from '@/routes';
import { index as agenda } from '@/routes/agenda';
import { index as berita } from '@/routes/berita';
import { index as kontak } from '@/routes/kontak';
import { index as download } from '@/routes/download';
import { index as galeri } from '@/routes/galeri';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { index as komunitas } from '@/routes/komunitas';
import { index as pelayanan } from '@/routes/pelayanan';
import {
    index as profil,
    pastor,
    sejarah,
    struktur,
    visiMisi,
    wilayah,
} from '@/routes/profil';

type FooterLink = {
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

type FooterGroup = {
    title: string;
    links: FooterLink[];
};

const MENU_UTAMA: FooterGroup = {
    title: 'Menu Utama',
    links: [
        { label: 'Beranda', href: home() },
        { label: 'Profil', href: profil() },
        { label: 'Jadwal Misa', href: jadwalMisa() },
        { label: 'Berita & Artikel', href: berita() },
        { label: 'Agenda', href: agenda() },
    ],
};

const PROFIL_PAROKI: FooterGroup = {
    title: 'Profil Paroki',
    links: [
        { label: 'Sejarah', href: sejarah() },
        { label: 'Visi & Misi', href: visiMisi() },
        { label: 'Wilayah Pelayanan', href: wilayah() },
        { label: 'Pastor', href: pastor() },
        { label: 'Struktur Kepengurusan', href: struktur() },
    ],
};

const PELAYANAN: FooterGroup = {
    title: 'Pelayanan & Komunitas',
    links: [
        { label: 'Pelayanan', href: pelayanan() },
        { label: 'Komunitas', href: komunitas() },
        { label: 'Galeri', href: galeri() },
        { label: 'Download', href: download() },
    ],
};

const MEDIA_SOCIAL = [
    { label: 'Facebook', value: '[ISI: tautan Facebook paroki]' },
    { label: 'Instagram', value: '[ISI: tautan Instagram paroki]' },
    { label: 'YouTube', value: '[ISI: tautan YouTube paroki]' },
];

const LEGAL = ['Kebijakan Privasi', 'Syarat & Ketentuan', 'Kontak Sekretariat'];

function FooterLinks({ group }: { group: FooterGroup }) {
    return (
        <div>
            <h2 className="text-caption-strong text-foreground">
                {group.title}
            </h2>

            <ul className="mt-4 space-y-2">
                {group.links.map((link: FooterLink) => (
                    <li key={link.label}>
                        <Link
                            href={link.href}
                            className="inline-flex min-h-11 items-center text-fine-print text-ink-muted transition-colors hover:text-red-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                        >
                            {link.label}
                        </Link>
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
    const { seo } = usePage<{ seo?: SeoDefaults }>().props;

    const parishName = seo?.siteName ?? 'HSPMTB Putussibau';

    return (
        <footer className="border-t border-divider-soft bg-canvas-soft">
            <PublicContainer className="py-16">
                <div className="grid gap-12 md:grid-cols-2 lg:grid-cols-6">
                    <div className="lg:col-span-2">
                        <div className="flex items-center gap-2">
                            <Church
                                className="size-5 text-red-on-surface"
                                aria-hidden="true"
                            />
                            <span className="text-body-strong text-foreground">
                                {parishName}
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
                        </ul>
                    </div>

                    <FooterLinks group={MENU_UTAMA} />
                    <FooterLinks group={PROFIL_PAROKI} />
                    <FooterLinks group={PELAYANAN} />

                    <div>
                        <h2 className="text-caption-strong text-foreground">
                            Media Sosial
                        </h2>

                        <ul className="mt-4 space-y-2">
                            {MEDIA_SOCIAL.map((item) => (
                                <li
                                    key={item.label}
                                    className="inline-flex min-h-11 items-center text-fine-print text-ink-muted-soft"
                                >
                                    {item.value}
                                </li>
                            ))}
                        </ul>

                        <h2 className="mt-8 text-caption-strong text-foreground">
                            Kontak
                        </h2>

                        <ul className="mt-4 space-y-2">
                            <li>
                                <Link
                                    href={kontak()}
                                    className="inline-flex min-h-11 items-center text-fine-print text-ink-muted transition-colors hover:text-red-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                                >
                                    Halaman kontak
                                </Link>
                            </li>
                            <li className="inline-flex min-h-11 items-center text-fine-print text-ink-muted-soft">
                                [ISI: tautan peta lokasi]
                            </li>
                        </ul>
                    </div>
                </div>

                <div className="mt-12 flex flex-col gap-4 border-t border-divider-soft pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-micro-legal text-ink-muted-soft">
                        &copy; {new Date().getFullYear()}{' '}
                        <span title={parishName}>{parishName}</span>. Seluruh
                        hak cipta dilindungi.
                    </p>

                    <ul className="flex flex-wrap gap-x-6 gap-y-2">
                        {LEGAL.map((label) => (
                            <li
                                key={label}
                                className="text-micro-legal text-ink-muted-soft"
                            >
                                {label}
                            </li>
                        ))}
                    </ul>
                </div>
            </PublicContainer>
        </footer>
    );
}

export default ParishFooter;
