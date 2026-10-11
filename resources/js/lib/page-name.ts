/**
 * Derives the Inertia page name from a globbed page file path.
 *
 * Pages live at `features/<audience>/<feature>/pages/`, and the name is the
 * path below that directory. `index.tsx` at the feature root is dropped rather
 * than read as a segment of its own, which is what keeps a single-page feature
 * from repeating its URL in the file path:
 *
 *   features/admin/akun/pages/profile.tsx      -> 'admin/akun/profile'
 *   features/admin/dashboard/pages/index.tsx    -> 'admin/dashboard'
 *   features/public/auth/pages/login.tsx       -> 'public/auth/login'
 *   features/public/beranda/pages/index.tsx    -> 'public/beranda'
 *
 * That exception stops at the feature root. An index deeper than that keeps its
 * folder, so the page a module keeps beside its own sub-pages is named after the
 * folder it sits in:
 *
 *   features/admin/jadwal-misa/pages/lokasi/index.tsx
 *                                               -> 'admin/jadwal-misa/lokasi/index'
 *
 * The alternative — dropping the index and naming the file after its folder —
 * would need one file to answer to two names, and two names to reach one file.
 * Its siblings already work without it: 'lokasi/tambah' and 'lokasi/ubah' are
 * plain paths in the same folder.
 *
 * Anything under a `components/` directory below `pages/` returns null. It
 * matches the same glob, and it is UI belonging to the pages beside it, not a
 * page any controller asks for.
 *
 * Its counterpart is App\Support\PageChunk, which walks the other direction for
 * the Blade template that preloads the page chunk. Both read the filesystem
 * rather than sharing a table, so the ArchitectureTest comparing the page list
 * against both sides is what keeps them in step.
 *
 * @see docs/DECISIONS.md D-30
 */
export function pageName(path: string): string | null {
    const segments = path.replace(/^\.\/features\//, '').split('/');

    if (segments.length < 4 || segments[2] !== 'pages') {
        return null;
    }

    const [audience, feature] = segments;
    const file = (segments[segments.length - 1] ?? '').replace(/\.tsx$/, '');
    const nested = segments.slice(3, -1);

    if ([...nested, file].includes('components')) {
        return null;
    }

    return file === 'index' && nested.length === 0
        ? `${audience}/${feature}`
        : [audience, feature, ...nested, file].join('/');
}
