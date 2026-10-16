import {
    ActiveSwitch,
    Field,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { Text } from '@/components/text';
import type { CommunityFormInstance } from './community-form-instance';
import { CommunityFormPanel } from './community-form-panel';

type Props = {
    form: CommunityFormInstance;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export function TabBergabungKontak({ form, labels, hints }: Props) {
    return (
        <CommunityFormPanel
            title="Informasi Bergabung dan Kontak"
            description="Lengkapi informasi persyaratan bergabung dan kontak komunitas ini. Informasi ini akan ditampilkan di halaman publik komunitas."
        >
            <div className="flex flex-col gap-4">
                <Field
                    id="community-eligibility"
                    label={labels.eligibility ?? 'Persyaratan Bergabung'}
                    error={form.errors.eligibility}
                >
                    <FormTextarea
                        id="community-eligibility"
                        value={form.data.eligibility}
                        onChange={(value) => form.setData('eligibility', value)}
                        rows={2}
                    />
                </Field>

                <Field
                    id="community-joining-info"
                    label={labels.joining_info ?? 'Cara Bergabung'}
                    error={form.errors.joining_info}
                >
                    <FormTextarea
                        id="community-joining-info"
                        value={form.data.joining_info}
                        onChange={(value) =>
                            form.setData('joining_info', value)
                        }
                        rows={2}
                    />
                </Field>

                <div className="grid gap-6 md:grid-cols-2">
                    <Field
                        id="community-contact-name"
                        label={labels.contact_name ?? 'Nama Kontak'}
                        error={form.errors.contact_name}
                    >
                        <FormInput
                            id="community-contact-name"
                            value={form.data.contact_name}
                            onChange={(value) =>
                                form.setData('contact_name', value)
                            }
                        />
                    </Field>

                    <Field
                        id="community-contact-phone"
                        label={labels.contact_phone ?? 'Nomor Kontak'}
                        error={form.errors.contact_phone}
                    >
                        <FormInput
                            id="community-contact-phone"
                            value={form.data.contact_phone}
                            onChange={(value) =>
                                form.setData('contact_phone', value)
                            }
                            inputMode="numeric"
                        />
                    </Field>
                </div>

                <ActiveSwitch
                    id="community-show-contact"
                    checked={form.data.show_contact}
                    onChange={(checked) =>
                        form.setData('show_contact', checked)
                    }
                    activeLabel={
                        labels.show_contact ??
                        'Tampilkan kontak di halaman publik'
                    }
                />

                <Text as="p" variant="caption" color="muted">
                    {hints.show_contact}
                </Text>
            </div>
        </CommunityFormPanel>
    );
}
