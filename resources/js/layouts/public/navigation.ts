import type { InertiaLinkProps } from '@inertiajs/react';
import { toUrl } from '@/lib/utils';
import type { IdentityDetails } from '@/types';

import { home } from '@/routes';
import { index as agenda } from '@/routes/agenda';
import { index as berita } from '@/routes/berita';
import { index as kontak } from '@/routes/kontak';
import { privacy, terms } from '@/routes/legal';
import { index as dokumen } from '@/routes/dokumen';
import { index as galeri } from '@/routes/galeri';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { index as komunitas } from '@/routes/komunitas';
import { index as pelayanan } from '@/routes/pelayanan';
import { index as areaIndex } from '@/routes/area';
import {
    index as profilIndex,
    pastor,
    sejarah,
    struktur,
    wilayah,
} from '@/routes/profil';

export type NavHref = NonNullable<InertiaLinkProps['href']>;

export type NavMatch = 'exact' | 'section' | 'prefix';

export type NavEntry = {
    label: string;
    href: NavHref;
    match: NavMatch;
};

export type NavItem =
    | { kind: 'link'; entry: NavEntry }
    | { kind: 'group'; label: string; children: NavEntry[] };

export type FooterLink = {
    label: string;
    href: NavHref;
};

export type FooterGroup = {
    title: string;
    links: FooterLink[];
};

export type LegalItem = {
    label: string;
    href: NavHref;
};

export function sectionPrefix(href: NavHref): string {
    const path = toUrl(href).split('?')[0];

    return path.replace(/\/[^/]+\/?$/, '') || '/';
}

export function isActiveEntry(
    entry: NavEntry,
    isCurrentUrl: (href: NavHref) => boolean,
    isCurrentOrParentUrl: (href: NavHref) => boolean,
): boolean {
    if (entry.match === 'exact') {
        return isCurrentUrl(entry.href);
    }

    if (entry.match === 'prefix') {
        return isCurrentOrParentUrl(sectionPrefix(entry.href));
    }

    return isCurrentOrParentUrl(entry.href);
}

export const PROFIL_CHILDREN: NavEntry[] = [
    { label: 'Ringkasan', href: profilIndex(), match: 'section' },
    { label: 'Sejarah', href: sejarah(), match: 'section' },
    { label: 'Wilayah Pelayanan', href: wilayah(), match: 'section' },
    { label: 'Area', href: areaIndex(), match: 'section' },
    { label: 'Pastor', href: pastor(), match: 'section' },
    { label: 'Struktur Kepengurusan', href: struktur(), match: 'section' },
];

export const PELAYANAN_CHILDREN: NavEntry[] = [
    { label: 'Pelayanan', href: pelayanan(), match: 'section' },
    { label: 'Komunitas', href: komunitas(), match: 'section' },
    { label: 'Galeri', href: galeri(), match: 'section' },
    { label: 'Dokumen', href: dokumen(), match: 'section' },
];

export const DRAWER_ENTRIES: NavItem[] = [
    { kind: 'link', entry: { label: 'Beranda', href: home(), match: 'exact' } },
    { kind: 'group', label: 'Profil', children: PROFIL_CHILDREN },
    {
        kind: 'link',
        entry: { label: 'Jadwal Misa', href: jadwalMisa(), match: 'section' },
    },
    {
        kind: 'link',
        entry: { label: 'Berita & Artikel', href: berita(), match: 'section' },
    },
    {
        kind: 'link',
        entry: { label: 'Agenda', href: agenda(), match: 'section' },
    },
    { kind: 'group', label: 'Pelayanan', children: PELAYANAN_CHILDREN },
    {
        kind: 'link',
        entry: { label: 'Kontak', href: kontak(), match: 'section' },
    },
];

export const DESKTOP_ENTRIES: NavItem[] = [
    { kind: 'link', entry: { label: 'Beranda', href: home(), match: 'exact' } },
    { kind: 'group', label: 'Profil', children: PROFIL_CHILDREN },
    {
        kind: 'link',
        entry: { label: 'Jadwal Misa', href: jadwalMisa(), match: 'section' },
    },
    {
        kind: 'link',
        entry: { label: 'Berita & Artikel', href: berita(), match: 'section' },
    },
    {
        kind: 'link',
        entry: { label: 'Agenda', href: agenda(), match: 'section' },
    },
    { kind: 'group', label: 'Pelayanan', children: PELAYANAN_CHILDREN },
    {
        kind: 'link',
        entry: { label: 'Kontak', href: kontak(), match: 'section' },
    },
];

export const SOCIALS = [
    { key: 'facebook', label: 'Facebook' },
    { key: 'instagram', label: 'Instagram' },
    { key: 'youtube', label: 'YouTube' },
    { key: 'whatsappChannel', label: 'Kanal WhatsApp' },
] as const;

export const LEGAL: LegalItem[] = [
    { label: 'Kebijakan Privasi', href: privacy() },
    { label: 'Syarat & Ketentuan', href: terms() },
    { label: 'Kontak Sekretariat', href: kontak() },
];

export function primaryIdentityName(
    identity: IdentityDetails | undefined,
): string {
    return identity?.shortName ?? identity?.name ?? 'Paroki';
}

export function secondaryIdentityName(
    identity: IdentityDetails | undefined,
): string | null {
    return identity?.name ?? identity?.tagline ?? null;
}
