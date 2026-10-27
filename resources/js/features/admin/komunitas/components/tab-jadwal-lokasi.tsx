import { Field, FormInput, FormTextarea } from '@/components/form-fields';
import type { CommunityFormInstance } from './community-form-instance';
import { CommunityFormPanel } from './community-form-panel';

type Props = {
    form: CommunityFormInstance;
    labels: Record<string, string>;
};

export function TabJadwalLokasi({ form, labels }: Props) {
    return (
        <CommunityFormPanel
            title="Jadwal & Lokasi"
            description="Lengkapi informasi jadwal dan lokasi pertemuan komunitas ini."
        >
            <div className="flex flex-col gap-4">
                <Field
                    id="community-meeting-schedule"
                    label={labels.meeting_schedule ?? 'Jadwal Pertemuan'}
                    error={form.errors.meeting_schedule}
                >
                    <FormTextarea
                        id="community-meeting-schedule"
                        value={form.data.meeting_schedule}
                        onChange={(value) =>
                            form.setData('meeting_schedule', value)
                        }
                        rows={2}
                        placeholder="Contoh: Jadwal pertemuan"
                    />
                </Field>

                <Field
                    id="community-meeting-location"
                    label={labels.meeting_location ?? 'Lokasi Pertemuan'}
                    error={form.errors.meeting_location}
                >
                    <FormInput
                        id="community-meeting-location"
                        value={form.data.meeting_location}
                        onChange={(value) =>
                            form.setData('meeting_location', value)
                        }
                        placeholder="Contoh: Aula paroki"
                    />
                </Field>
            </div>
        </CommunityFormPanel>
    );
}
