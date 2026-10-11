import { describe, expect, it } from 'vite-plus/test';

/*
 * lib/page-name.ts.
 *
 * The frontend half of the page naming rule, whose PHP half is
 * App\Support\PageChunk. The two are compared against each other by an
 * architecture test walking the real files, so what needs testing here is the
 * part that cannot be checked that way: how a name is derived from a path the
 * application does not happen to contain.
 *
 * The rule exists because of a layout that broke: a page with sub-pages moved
 * into a folder named after itself, so pages/lokasi/index.tsx sits beside
 * pages/lokasi/tambah.tsx. An earlier version dropped every trailing `index`,
 * which quietly turned that file into a page named after its folder and left
 * the controller asking for a name the PHP side could not produce.
 */

import { pageName } from '@/lib/page-name';

describe('pageName', () => {
    it('names a page by its path below pages/', () => {
        expect(pageName('./features/admin/akun/pages/profile.tsx')).toBe(
            'admin/akun/profile',
        );
        expect(pageName('./features/public/auth/pages/login.tsx')).toBe(
            'public/auth/login',
        );
    });

    it('drops the index of the feature itself', () => {
        expect(pageName('./features/admin/dashboard/pages/index.tsx')).toBe(
            'admin/dashboard',
        );
        expect(pageName('./features/public/beranda/pages/index.tsx')).toBe(
            'public/beranda',
        );
    });

    it('keeps the folder of a page that sits inside one', () => {
        // The shape that broke the jadwal-misa module: a page moved into a
        // folder beside its sub-pages, so pages/lokasi/index.tsx sits next to
        // pages/lokasi/tambah.tsx. The name keeps the folder, because that is
        // what makes the name the file's own path — the same thing every other
        // page in the application does.
        expect(
            pageName('./features/admin/jadwal-misa/pages/lokasi/index.tsx'),
        ).toBe('admin/jadwal-misa/lokasi/index');
    });

    it('keeps a sub-page of a folder named after itself', () => {
        expect(
            pageName('./features/admin/jadwal-misa/pages/lokasi/tambah.tsx'),
        ).toBe('admin/jadwal-misa/lokasi/tambah');
        expect(
            pageName('./features/admin/jadwal-misa/pages/lokasi/ubah.tsx'),
        ).toBe('admin/jadwal-misa/lokasi/ubah');
    });

    it('ignores components kept below pages', () => {
        // A form component beside the pages that use it matches the same glob.
        // Admitting it would register a page no controller asks for, under a
        // name the PHP side refuses — so the two halves would disagree.
        expect(
            pageName(
                './features/admin/jadwal-misa/pages/lokasi/components/location-form.tsx',
            ),
        ).toBeNull();
        expect(
            pageName(
                './features/admin/jadwal-misa/pages/pemberitahuan/components/schedule-notice-form.tsx',
            ),
        ).toBeNull();
    });

    it('ignores a path that is not a page at all', () => {
        expect(pageName('./components/app-sidebar.tsx')).toBeNull();
        expect(
            pageName('./features/admin/jadwal-misa/components/data-table.tsx'),
        ).toBeNull();
    });
});
