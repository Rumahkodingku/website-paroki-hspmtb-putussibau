import { Field, FormInput } from '@/components/form-fields';
import type { CommunityFormInstance } from './community-form-instance';
import { CommunityFormPanel } from './community-form-panel';

type Props = {
    form: CommunityFormInstance;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

type SocialField = 'facebook' | 'instagram' | 'tiktok' | 'youtube' | 'website';

const LINKS: { field: SocialField; label: string; placeholder: string }[] = [
    {
        field: 'facebook',
        label: 'Facebook',
        placeholder: 'https://facebook.com/nama-komunitas',
    },
    {
        field: 'instagram',
        label: 'Instagram',
        placeholder: 'https://instagram.com/nama-komunitas',
    },
    {
        field: 'tiktok',
        label: 'TikTok',
        placeholder: 'https://tiktok.com/@nama-komunitas',
    },
    {
        field: 'youtube',
        label: 'YouTube',
        placeholder: 'https://youtube.com/@nama-komunitas',
    },
    {
        field: 'website',
        label: 'Situs Web Komunitas',
        placeholder: 'https://namakomunitas.or.id',
    },
];

export function TabMediaSosial({ form, labels, hints }: Props) {
    return (
        <CommunityFormPanel
            title="Media Sosial"
            description="Lengkapi informasi media sosial komunitas ini. Informasi ini akan ditampilkan di halaman publik komunitas."
        >
            <div className="flex flex-col gap-6">
                {LINKS.map(({ field, label, placeholder }) => (
                    <Field
                        key={field}
                        id={`community-${field}`}
                        label={labels[field] ?? label}
                        hint={hints[field]}
                        error={form.errors[field]}
                    >
                        <FormInput
                            id={`community-${field}`}
                            value={form.data[field]}
                            onChange={(value) => form.setData(field, value)}
                            type="url"
                            inputMode="url"
                            placeholder={placeholder}
                        />
                    </Field>
                ))}
            </div>
        </CommunityFormPanel>
    );
}
