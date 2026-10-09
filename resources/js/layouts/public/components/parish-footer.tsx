import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import { type LucideIcon, Church, Mail, MapPin, Phone } from 'lucide-react';
import { PublicContainer } from '@/components/public-container';
import { Text } from '@/components/text';
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

/*
 * The class every footer link carries, in one place.
 *
 * It was written out twice before - once for the three navigation groups and
 * once for the single "Halaman kontak" link - and the two copies had already
 * begun to diverge in review. A constant is the smallest thing that stops a
 * third.
 */
const FOOTER_LINK =
    'inline-flex min-h-11 items-center transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';

function FooterLinks({ group }: { group: FooterGroup }) {
    return (
        <div>
            <Text as="h2" variant="caption-strong" color="foreground">
                {group.title}
            </Text>

            <ul className="mt-4 space-y-2">
                {group.links.map((link: FooterLink) => (
                    <li key={link.label}>
                        {/*
                            The anchor keeps the touch target and the focus
                            ring; the Text keeps the type. Splitting them this
                            way is what lets the size come from the design
                            system without Text having to grow an `asChild` for
                            a component that is not a text element.
                        */}
                        <Link href={link.href} className={FOOTER_LINK}>
                            <Text
                                as="span"
                                variant="fine-print"
                                className="text-ink-muted hover:text-red-on-surface"
                            >
                                {link.label}
                            </Text>
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
        <li className="flex items-start gap-2">
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            {/*
                The icon and the value are in a flex row, so the value keeps its
                own line box: an icon with text wrapping around it at
                items-start is the one place a phone-sized footer goes wrong.
            */}
            <Text as="span" variant="fine-print" className="text-ink-muted">
                <span className="sr-only">{label}: </span>
                {value}
            </Text>
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
                            <Text
                                as="span"
                                variant="body-strong"
                                color="foreground"
                            >
                                {parishName}
                            </Text>
                        </div>

                        <Text
                            as="p"
                            variant="fine-print"
                            className="mt-4 max-w-sm text-ink-muted"
                        >
                            [ISI: deskripsi singkat paroki. Satu atau dua
                            kalimat yang menjelaskan siapa kami.]
                        </Text>

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
                        <Text
                            as="h2"
                            variant="caption-strong"
                            color="foreground"
                        >
                            Media Sosial
                        </Text>

                        <ul className="mt-4 space-y-2">
                            {MEDIA_SOCIAL.map((item) => (
                                <li
                                    key={item.label}
                                    className="inline-flex min-h-11 items-center"
                                >
                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        color="subtle"
                                    >
                                        {item.value}
                                    </Text>
                                </li>
                            ))}
                        </ul>

                        <Text
                            as="h2"
                            variant="caption-strong"
                            color="foreground"
                            className="mt-8"
                        >
                            Kontak
                        </Text>

                        <ul className="mt-4 space-y-2">
                            <li>
                                <Link href={kontak()} className={FOOTER_LINK}>
                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        className="text-ink-muted hover:text-red-on-surface"
                                    >
                                        Halaman kontak
                                    </Text>
                                </Link>
                            </li>
                            <li className="inline-flex min-h-11 items-center">
                                <Text
                                    as="span"
                                    variant="fine-print"
                                    color="subtle"
                                >
                                    [ISI: tautan peta lokasi]
                                </Text>
                            </li>
                        </ul>
                    </div>
                </div>

                <div className="mt-12 flex flex-col gap-4 border-t border-divider-soft pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <Text as="p" variant="micro-legal" color="subtle">
                        &copy; {new Date().getFullYear()}{' '}
                        <span title={parishName}>{parishName}</span>. Seluruh
                        hak cipta dilindungi.
                    </Text>

                    <ul className="flex flex-wrap gap-x-6 gap-y-2">
                        {LEGAL.map((label) => (
                            <li key={label}>
                                <Text
                                    as="span"
                                    variant="micro-legal"
                                    color="subtle"
                                >
                                    {label}
                                </Text>
                            </li>
                        ))}
                    </ul>
                </div>
            </PublicContainer>
        </footer>
    );
}

export default ParishFooter;
