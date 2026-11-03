import { Link } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { edit as editSeo } from '@/routes/seo';

/**
 * The bridge between the global fallbacks and the per-page overrides.
 *
 * The settings form owns seo_default_*; this notice explains what those values
 * do and points an editor at the page that owns the per-page metadata.
 */
export function SeoGlobalNotice() {
    return (
        <Alert role="status">
            <Info className="size-4" aria-hidden="true" />
            <AlertTitle>SEO per halaman</AlertTitle>
            <AlertDescription>
                <p>
                    Judul, deskripsi, dan gambar di tab ini adalah fallback
                    global. Untuk mengatur halaman tertentu, buka SEO Halaman.
                </p>
                <Button asChild variant="outline" size="sm">
                    <Link href={editSeo()}>Buka SEO Halaman</Link>
                </Button>
            </AlertDescription>
        </Alert>
    );
}
