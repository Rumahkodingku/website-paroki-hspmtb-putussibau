import { describe, expect, it } from 'vite-plus/test';
import { screen, within } from '@testing-library/react';
import type { ArticleCardData } from '@/components/article-card';
import type { EventCardData } from '@/components/event-card';
import type { NextMassCardData } from '@/components/next-mass-card';
import Beranda from '@/features/public/beranda/pages/index';
import type {
    HomepageProps,
    HomeService,
} from '@/features/public/beranda/types';
import type { SharedProps } from '@/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The homepage composition.
 *
 * The backend tests own the queries and the publication rules. These tests are
 * about what a visitor sees once the props arrive: the hero copy, the links,
 * whether an empty section leaves a heading behind, and whether the contact
 * map is only drawn when the server resolved a URL.
 */

const IDENTITY = {
    name: 'Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau',
    shortName: 'HSPMTB',
    tagline: 'Hati Santa Perawan Maria Tak Bernoda',
    shortDescription: 'Komunitas umat Katolik di Putussibau.',
    logoUrl: null,
    logoAlt: null,
};

const NEXT_MASS: NextMassCardData = {
    kind: 'rutin',
    starts_at: '2026-10-18T07:30:00+07:00',
    name: 'Misa Minggu Pagi',
    location: {
        id: 1,
        name: 'Gereja Paroki',
        type: 'gereja_paroki',
        address: null,
        mapsUrl: null,
    },
};

const ARTICLE: ArticleCardData = {
    id: 1,
    title: 'Umat merayakan Paskah',
    slug: 'umat-merayakan-paskah',
    excerpt: 'Ringkasan berita paroki.',
    image: null,
    image_alt: null,
    category: null,
    published_at: '2026-10-18T00:00:00+00:00',
};

const EVENT: EventCardData = {
    id: 1,
    title: 'Kegiatan Paroki',
    slug: 'kegiatan-paroki',
    summary: 'Ringkasan kegiatan.',
    start_at: '2026-10-20T09:00:00+07:00',
    end_at: null,
    is_all_day: false,
    location_name: 'Gereja Paroki',
    location_url: null,
    poster: null,
    poster_alt: null,
    status: 'scheduled',
    status_label: 'Terjadwal',
    category: null,
};

const SERVICE: HomeService = {
    id: 1,
    title: 'Baptis',
    slug: 'baptis',
    thumbnail: null,
    thumbnail_alt: null,
    summary: 'Pelayanan baptis paroki.',
    is_emergency: false,
};

const GALLERY_PHOTO = {
    id: 1,
    caption: null,
    alt_text: 'Umat berkumpul di gereja',
    thumb_url: '/storage/thumb.webp',
    url: '/storage/medium.webp',
    width: 1200,
    height: 900,
    album: {
        title: 'Paskah 2026',
        slug: 'paskah-2026',
    },
};

const ILLUSTRATION = {
    id: 'community',
    src: '/illustrations/gathering-1200.webp',
    srcSet: '/illustrations/gathering-600.webp 600w, /illustrations/gathering-1200.webp 1200w',
    sizes: '(min-width: 768px) 33vw, 50vw',
    alt: 'Ilustrasi: orang-orang berkumpul di tepi pantai',
    width: 1200,
    height: 800,
};

function props(overrides: Partial<HomepageProps> = {}): HomepageProps {
    return {
        nextMass: null,
        news: [],
        events: [],
        devotion: null,
        services: [],
        galleryPhotos: [],
        galleryIllustrations: [],
        hero: {
            title: null,
            description: null,
            image: '/hero-section.webp',
            imageAlt: 'Foto gereja paroki pada malam hari',
            ogImage: '/hero-section.png',
            width: 1672,
            height: 941,
            motto: null,
        },
        sections: {
            devotion: true,
            services: true,
            gallery: true,
        },
        ...overrides,
    };
}

function renderHome(
    overrides: Partial<HomepageProps> = {},
    shared: Partial<SharedProps> = {},
) {
    return renderWithInertia(<Beranda {...props(overrides)} />, {
        props: {
            identity: IDENTITY,
            seo: {
                appUrl: 'http://localhost',
                siteName: IDENTITY.name,
                title: null,
                description: null,
                ogImage: null,
            },
            ...shared,
        },
    });
}

describe('Beranda', () => {
    it('renders the hero with the parish identity and both CTAs', () => {
        renderHome();

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: IDENTITY.name,
            }),
        ).toBeInTheDocument();

        expect(
            screen.getByRole('link', { name: /Lihat Jadwal Misa/ }),
        ).toHaveAttribute('href', '/jadwal-misa');
        expect(
            screen.getByRole('link', { name: 'Kenali Paroki' }),
        ).toHaveAttribute('href', '/profil/sejarah');

        expect(
            screen.getByRole('img', { name: /Foto gereja paroki/ }),
        ).toHaveAttribute('src', '/hero-section.webp');
    });

    it('uses the admin hero title when one is configured', () => {
        renderHome({
            hero: {
                ...props().hero,
                title: 'Sambut umat di rumah',
                description: 'Jadwal misa dan pelayanan paroki.',
                motto: 'Di mana dua atau tiga orang berkumpul dalam nama-Ku.',
            },
        });

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Sambut umat di rumah',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Jadwal misa dan pelayanan paroki.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/Di mana dua atau tiga orang berkumpul/),
        ).toBeInTheDocument();
    });

    it('renders the four quick links as navigation', () => {
        renderHome();

        const links = within(
            screen.getByRole('navigation', { name: 'Pintasan beranda' }),
        );

        expect(
            links.getByRole('link', { name: /Jadwal Misa/ }),
        ).toHaveAttribute('href', '/jadwal-misa');
        expect(
            links.getByRole('link', { name: /Berita & Artikel/ }),
        ).toHaveAttribute('href', '/berita');
        expect(links.getByRole('link', { name: /Agenda/ })).toHaveAttribute(
            'href',
            '/agenda',
        );
        expect(links.getByRole('link', { name: /Pelayanan/ })).toHaveAttribute(
            'href',
            '/pelayanan',
        );
    });

    it('keeps the next mass block visible when nothing is scheduled', () => {
        renderHome();

        expect(
            screen.getByRole('heading', { level: 2, name: 'Misa Berikutnya' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Belum ada jadwal misa')).toBeInTheDocument();
    });

    it('renders the next mass when the resolver sent one', () => {
        renderHome({ nextMass: NEXT_MASS });

        expect(screen.getByText('Misa Minggu Pagi')).toBeInTheDocument();
        expect(screen.getByText('Gereja Paroki')).toBeInTheDocument();
        expect(
            screen.getByText('Jadwal rutin mingguan paroki'),
        ).toBeInTheDocument();
    });

    it('leaves no heading behind when optional sections are empty', () => {
        renderHome();

        for (const heading of [
            'Berita & Artikel',
            'Agenda Mendatang',
            'Renungan Terbaru',
            'Pelayanan Sakramen',
            'Galeri Kegiatan',
            'Hubungi Kami',
        ]) {
            expect(screen.queryByRole('heading', { name: heading })).toBeNull();
        }
    });

    it('renders news and agenda when both have data', () => {
        renderHome({ news: [ARTICLE], events: [EVENT] });

        expect(
            screen.getByRole('heading', { name: 'Berita & Artikel' }),
        ).toBeInTheDocument();
        expect(screen.getByText(ARTICLE.title)).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Agenda Mendatang' }),
        ).toBeInTheDocument();
        expect(screen.getByText(EVENT.title)).toBeInTheDocument();
    });

    it('honours the section toggles even when data arrives', () => {
        renderHome({
            devotion: ARTICLE,
            services: [SERVICE],
            galleryPhotos: [GALLERY_PHOTO],
            sections: {
                devotion: false,
                services: false,
                gallery: false,
            },
        });

        expect(
            screen.queryByRole('heading', { name: 'Renungan Terbaru' }),
        ).toBeNull();
        expect(
            screen.queryByRole('heading', { name: 'Pelayanan Sakramen' }),
        ).toBeNull();
        expect(
            screen.queryByRole('heading', { name: 'Galeri Kegiatan' }),
        ).toBeNull();
    });

    it('renders the reflection, services, and gallery when enabled', () => {
        renderHome({
            devotion: ARTICLE,
            services: [SERVICE],
            galleryPhotos: [GALLERY_PHOTO],
        });

        expect(
            screen.getByRole('heading', { name: 'Renungan Terbaru' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Pelayanan Sakramen' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Baptis')).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Galeri Kegiatan' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Lihat album Paskah 2026/ }),
        ).toHaveAttribute('href', '/galeri/paskah-2026');
    });

    it('shows the contact map only when the shared contact prop has one', () => {
        renderHome(
            {},
            {
                contact: {
                    address: 'Jalan Contoh No. 1',
                    phone: null,
                    whatsapp: null,
                    email: null,
                    officeHours: null,
                    mapsEmbedUrl:
                        'https://www.google.com/maps?q=Paroki&z=17&output=embed',
                    mapsLink:
                        'https://www.google.com/maps/place/Paroki/@-0.02,111.7,17z',
                    homeShowContact: true,
                    social: {
                        facebook: null,
                        instagram: null,
                        youtube: null,
                        whatsappChannel: null,
                    },
                },
            },
        );

        expect(
            screen.getByRole('heading', { name: 'Hubungi Kami' }),
        ).toBeInTheDocument();
        expect(screen.getByTitle('Peta lokasi paroki')).toHaveAttribute(
            'src',
            'https://www.google.com/maps?q=Paroki&z=17&output=embed',
        );
    });

    it('hides the contact block when home_show_contact is false', () => {
        renderHome(
            {},
            {
                contact: {
                    address: 'Jalan Contoh No. 1',
                    phone: null,
                    whatsapp: null,
                    email: null,
                    officeHours: null,
                    mapsEmbedUrl: null,
                    mapsLink: null,
                    homeShowContact: false,
                    social: {
                        facebook: null,
                        instagram: null,
                        youtube: null,
                        whatsappChannel: null,
                    },
                },
            },
        );

        expect(
            screen.queryByRole('heading', { name: 'Hubungi Kami' }),
        ).toBeNull();
    });

    it('renders an illustration fallback image on a news card', () => {
        renderHome({
            news: [
                {
                    ...ARTICLE,
                    image: '/illustrations/activity-960.webp',
                    image_alt: 'Ilustrasi: orang bekerja di depan komputer',
                    image_is_illustration: true,
                },
            ],
        });

        expect(screen.getByRole('img', { name: /Ilustrasi:/ })).toHaveAttribute(
            'src',
            '/illustrations/activity-960.webp',
        );
    });

    it('does not label a real photograph', () => {
        renderHome({
            news: [
                {
                    ...ARTICLE,
                    image: '/storage/media/real.webp',
                    image_alt: 'Foto kegiatan paroki',
                },
            ],
        });

        expect(screen.queryByText('Ilustrasi')).toBeNull();
    });

    it('renders a labeled illustration strip when the gallery is empty', () => {
        renderHome({ galleryIllustrations: [ILLUSTRATION] });

        expect(
            screen.getByRole('heading', { name: 'Ilustrasi' }),
        ).toBeInTheDocument();
        expect(screen.getAllByText('Ilustrasi').length).toBeGreaterThan(1);
        expect(
            screen.getByRole('img', { name: /orang-orang berkumpul/ }),
        ).toHaveAttribute('src', ILLUSTRATION.src);
    });

    it('renders an illustration thumbnail for a service without media', () => {
        renderHome({
            services: [
                {
                    ...SERVICE,
                    thumbnail: '/illustrations/study-960.webp',
                    thumbnail_alt: 'Ilustrasi: meja belajar',
                    thumbnail_is_illustration: true,
                },
            ],
        });

        expect(screen.getByText('Ilustrasi')).toBeInTheDocument();
    });

    it('gives the hero heading the fluid display-xl step', () => {
        renderHome();

        // DESIGN-v2 display-xl is a clamp() between 36px (mobile) and 52px
        // (desktop), so the step-down is in the token rather than a
        // breakpoint-specific override.
        expect(screen.getByRole('heading', { level: 1 }).className).toContain(
            'text-4xl',
        );
    });
});
