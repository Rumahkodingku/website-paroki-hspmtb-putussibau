import { MediaField } from '@/components/media-field';
import type { CommunityFormInstance } from './community-form-instance';
import { CommunityFormPanel } from './community-form-panel';

type Props = {
    form: CommunityFormInstance;
    labels: Record<string, string>;
    hints: Record<string, string>;
    logoUrl: string | null;
    thumbnailUrl: string | null;
};

export function TabLogoThumbnail({
    form,
    labels,
    hints,
    logoUrl,
    thumbnailUrl,
}: Props) {
    return (
        <CommunityFormPanel
            title="Logo dan Thumbnail"
            description="Unggah logo dan thumbnail untuk komunitas ini. Logo akan ditampilkan di halaman publik, sedangkan thumbnail akan digunakan sebagai gambar preview di daftar komunitas."
        >
            <div className="flex flex-col gap-4">
                <MediaField
                    id="community-logo"
                    label={labels.logo ?? 'Logo'}
                    hint={hints.logo}
                    altValue={form.data.logo_alt}
                    onAltChange={(value) => form.setData('logo_alt', value)}
                    altLabel={labels.logo_alt ?? 'Teks alternatif logo'}
                    altError={form.errors.logo_alt}
                    mediaId={form.data.logo_media_id}
                    onMediaChange={(mediaId) =>
                        form.setData('logo_media_id', mediaId)
                    }
                    currentUrl={logoUrl}
                    fieldName="logo"
                    emptyHint="Belum ada logo"
                />

                <MediaField
                    id="community-thumbnail"
                    label={labels.thumbnail ?? 'Thumbnail'}
                    hint={hints.thumbnail}
                    altValue={form.data.thumbnail_alt}
                    onAltChange={(value) =>
                        form.setData('thumbnail_alt', value)
                    }
                    altLabel={
                        labels.thumbnail_alt ?? 'Teks alternatif thumbnail'
                    }
                    altError={form.errors.thumbnail_alt}
                    mediaId={form.data.thumbnail_media_id}
                    onMediaChange={(mediaId) =>
                        form.setData('thumbnail_media_id', mediaId)
                    }
                    currentUrl={thumbnailUrl}
                    fieldName="thumbnail"
                    emptyHint="Belum ada thumbnail"
                />
            </div>
        </CommunityFormPanel>
    );
}
