import { ActiveSwitch } from '@/components/form-fields';
import { Text } from '@/components/text';
import type { CommunityFormInstance } from './community-form-instance';
import { CommunityFormPanel } from './community-form-panel';

type Props = {
    form: CommunityFormInstance;
    labels: Record<string, string>;
    sections: Record<string, string>;
    statusLabel: string;
};

export function TabPublikasi({ form, labels, sections, statusLabel }: Props) {
    return (
        <CommunityFormPanel
            title="Publikasi"
            description="Kelola status publikasi komunitas ini. Publikasi komunitas akan menampilkan komunitas di halaman publik."
        >
            <ActiveSwitch
                id="community-published"
                checked={form.data.is_published}
                onChange={(checked) => form.setData('is_published', checked)}
                activeLabel={labels.is_published ?? 'Publikasi'}
            />

            <Text as="p" variant="caption" color="muted">
                {form.data.is_published && form.data.status === 'active'
                    ? (sections.publication_live ??
                      'Dipublikasikan: komunitas tampil di halaman publik.')
                    : (sections.publication_draft ??
                      'Draf: komunitas belum tampil di halaman publik.')}
                {form.data.status !== 'active'
                    ? ` Status "${statusLabel}" tidak ditampilkan di halaman publik.`
                    : ''}
            </Text>
        </CommunityFormPanel>
    );
}
