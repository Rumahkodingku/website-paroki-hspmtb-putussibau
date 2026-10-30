import { useForm } from '@inertiajs/react';
import { Field, FormActions, FormInput } from '@/components/form-fields';
import type { FormMeta } from '@/types/news-agenda';
import type { EventCategoryFormData } from '../../../types/event-category';

export type EventCategoryFormInstance = ReturnType<
    typeof useForm<EventCategoryFormData>
>;

export function emptyEventCategoryFormData(): EventCategoryFormData {
    return { name: '', slug: '' };
}

type Props = {
    initial: EventCategoryFormData;
    labels: FormMeta['labels'];
    submitLabel: string;
    onSubmit: (form: EventCategoryFormInstance) => void;
    onCancel: () => void;
};

export function EventCategoryForm({
    initial,
    labels,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<EventCategoryFormData>(initial);

    return (
        <form
            // See AGENTS.md: with `required` and no noValidate the browser
            // validates first and the server never runs, so the Indonesian
            // message in lang/id never appears.
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <Field
                id="event-category-name"
                label={labels.name ?? 'Nama kategori'}
                error={form.errors.name}
                required
            >
                <FormInput
                    id="event-category-name"
                    value={form.data.name}
                    onChange={(value) => form.setData('name', value)}
                    placeholder="Contoh: Liturgi"
                    required
                />
            </Field>

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
