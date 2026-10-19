import { useForm } from '@inertiajs/react';
import { Field, FormActions, FormInput } from '@/components/form-fields';
import type { FormMeta } from '@/types/news-agenda';
import type { EventCategoryFormData } from '../../../types/event-category';

export type EventCategoryFormInstance = ReturnType<
    typeof useForm<EventCategoryFormData>
>;

export function emptyEventCategoryFormData(): EventCategoryFormData {
    // The seed palette's first entry, so the field is never empty and the
    // admin has something valid to keep rather than a blank to guess at.
    return { name: '', slug: '', color: '#2E7D32' };
}

type Props = {
    initial: EventCategoryFormData;
    labels: FormMeta['labels'];
    hints: FormMeta['hints'];
    submitLabel: string;
    onSubmit: (form: EventCategoryFormInstance) => void;
    onCancel: () => void;
};

/**
 * A colour well beside the text field, not instead of it.
 *
 * The hex is also typed in, because a native colour input cannot express what a
 * parish wants to enter and because the value has to be validated as a string.
 * The swatch is a preview; the field is the value.
 */
function ColourPreview({ value }: { value: string }) {
    const valid = /^#[0-9A-Fa-f]{6}$/.test(value);

    return (
        <span
            aria-hidden="true"
            className="inline-block size-6 shrink-0 rounded-sm border border-border"
            style={{
                backgroundColor: valid ? value : 'transparent',
            }}
        />
    );
}

export function EventCategoryForm({
    initial,
    labels,
    hints,
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
            onSubmit={(event) => {
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

            <Field
                id="event-category-slug"
                label={labels.slug ?? 'Slug'}
                hint={hints.slug}
                error={form.errors.slug}
                required
            >
                <FormInput
                    id="event-category-slug"
                    value={form.data.slug}
                    onChange={(value) => form.setData('slug', value)}
                    placeholder="contoh: liturgi"
                    required
                />
            </Field>

            <Field
                id="event-category-color"
                label={labels.color ?? 'Warna penanda'}
                hint={hints.color}
                error={form.errors.color}
                required
            >
                <div className="flex items-center gap-3">
                    <ColourPreview value={form.data.color} />
                    <FormInput
                        id="event-category-color"
                        value={form.data.color}
                        onChange={(value) => form.setData('color', value)}
                        placeholder="#2E7D32"
                        required
                    />
                </div>
            </Field>

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
