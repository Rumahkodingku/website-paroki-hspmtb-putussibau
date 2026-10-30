import type { InertiaLinkProps } from '@inertiajs/react';
import { home } from '@/routes';
import { privacy, terms } from '@/routes/legal';
import { index as agenda } from '@/routes/agenda';
import { index as berita } from '@/routes/berita';
import { index as dokumen } from '@/routes/dokumen';
import { index as galeri } from '@/routes/galeri';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { index as komunitas } from '@/routes/komunitas';
import { index as pelayanan } from '@/routes/pelayanan';
import {
    index as profilIndex,
    pastor,
    sejarah,
    struktur,
    wilayah,
} from '@/routes/profil';

export type FooterLink = {
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type FooterGroup = {
    title: string;
    links: FooterLink[];
};

export type LegalItem = {
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export const MENU_UTAMA: FooterGroup = {
    title: 'Menu Utama',
    links: [
        { label: 'Beranda', href: home() },
        { label: 'Profil Paroki', href: profilIndex() },
        { label: 'Jadwal Misa', href: jadwalMisa() },
        { label: 'Berita & Artikel', href: berita() },
        { label: 'Agenda', href: agenda() },
        { label: 'Galeri', href: galeri() },
    ],
};

export const PELAYANAN: FooterGroup = {
    title: 'Pelayanan',
    links: [
        { label: 'Sakramen Baptis', href: pelayanan() },
        { label: 'Komuni Pertama', href: pelayanan() },
        { label: 'Krisma', href: pelayanan() },
        { label: 'Pernikahan', href: pelayanan() },
        { label: 'Pengurapan Orang Sakit', href: pelayanan() },
        { label: 'Pelayanan Lainnya', href: pelayanan() },
    ],
};

export const INFORMASI: FooterGroup = {
    title: 'Informasi Paroki',
    links: [
        { label: 'Sejarah Paroki', href: sejarah() },
        { label: 'Pastor Paroki', href: pastor() },
        { label: 'Struktur Kepengurusan', href: struktur() },
        { label: 'Wilayah Pelayanan', href: wilayah() },
        { label: 'Komunitas', href: komunitas() },
        { label: 'Dokumen & Unduhan', href: dokumen() },
    ],
};

export const LINK_CLASS =
    'inline-flex min-h-8 items-center text-fine-print text-muted-foreground transition-colors hover:text-primary-on-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';

export const LEGAL: LegalItem[] = [
    { label: 'Kebijakan Privasi', href: privacy() },
    { label: 'Syarat & Ketentuan', href: terms() },
];
