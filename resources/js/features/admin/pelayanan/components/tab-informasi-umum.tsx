import type { FormEvent } from 'react';
import { Info } from 'lucide-react';
import {
    ChoiceSelect,
    Field,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { MediaField } from '@/components/media-field';
import { RichTextEditor } from '@/components/rich-text-editor';
import { Text } from '@/components/text';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { ServiceFormInstance } from './service-form-instance';

type Props = {
    form: ServiceFormInstance;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    categoryOptions: Array<{ value: string; label: string }>;
    /** Browser URL of the stored thumbnail, for the edit form preview. */
    currentThumbnailUrl: string | null;
    onSubmit: (event: FormEvent) => void;
};

/**
 * The Informasi Umum tab: the service's identity, its rich-text introduction,
 * and the status/order panel.
 *
 * It renders its own <form> against the shared form instance — see
 * service-form-instance.ts for why the scalar tabs are not wrapped in one form.
 */
export function TabInformasiUmum({
    form,
    labels,
    hints,
    copy,
    categoryOptions,
    currentThumbnailUrl,
    onSubmit,
}: Props) {
    return (
        <form noValidate onSubmit={onSubmit} className="space-y-6">
            <div className="space-y-1">
                <Text as="h3" variant="h3">
                    Informasi Umum
                </Text>
                <Text as="p" variant="caption" color="muted">
                    Lengkapi informasi umum pelayanan ini, termasuk nama,
                    kategori, dLL. Informasi ini akan ditampilkan di halaman
                    pelayanan
                </Text>
            </div>

            <div className="grid gap-8 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Field
                        id="service-title"
                        label={labels.title}
                        error={form.errors.title}
                        required
                    >
                        <FormInput
                            id="service-title"
                            value={form.data.title}
                            onChange={(value) => form.setData('title', value)}
                            placeholder={copy.placeholder_name}
                            required
                        />
                    </Field>

                    <Field
                        id="service-slug"
                        label={labels.slug}
                        hint={hints.slug}
                        error={form.errors.slug}
                        required
                    >
                        <FormInput
                            id="service-slug"
                            value={form.data.slug}
                            onChange={(value) => form.setData('slug', value)}
                            placeholder={copy.placeholder_slug}
                            required
                        />
                    </Field>

                    <Field
                        id="service-category"
                        label={labels.service_category_id}
                        error={form.errors.service_category_id}
                        required
                    >
                        <ChoiceSelect
                            id="service-category"
                            value={form.data.service_category_id}
                            onChange={(value) =>
                                form.setData('service_category_id', value)
                            }
                            options={categoryOptions}
                            placeholder={copy.placeholder_category}
                        />
                    </Field>

                    <MediaField
                        id="service-thumbnail"
                        label={labels.thumbnail_media_id}
                        hint={hints.thumbnail_media_id}
                        altValue={form.data.thumbnail_alt}
                        onAltChange={(value) =>
                            form.setData('thumbnail_alt', value)
                        }
                        altLabel={labels.thumbnail_alt}
                        altError={form.errors.thumbnail_alt}
                        mediaId={form.data.thumbnail_media_id}
                        onMediaChange={(id) =>
                            form.setData('thumbnail_media_id', id)
                        }
                        currentUrl={currentThumbnailUrl}
                        fieldName="thumbnail_media_id"
                        emptyHint={copy.empty_thumbnail}
                    />

                    <Field
                        id="service-summary"
                        label={labels.summary}
                        hint={hints.summary}
                        error={form.errors.summary}
                    >
                        <FormTextarea
                            id="service-summary"
                            value={form.data.summary}
                            onChange={(value) => form.setData('summary', value)}
                            rows={2}
                            placeholder={copy.placeholder_summary}
                        />
                    </Field>

                    <div className="space-y-2">
                        <Label htmlFor="service-introduction">
                            {labels.introduction}
                        </Label>
                        <RichTextEditor
                            id="service-introduction"
                            value={form.data.introduction}
                            onChange={(html) =>
                                form.setData('introduction', html)
                            }
                            label={labels.introduction}
                        />
                        {form.errors.introduction ? (
                            <Text as="p" variant="caption" color="danger">
                                {form.errors.introduction}
                            </Text>
                        ) : null}
                    </div>
                </div>

                <aside className="space-y-5 rounded-lg border border-hairline p-5">
                    <Field
                        id="service-sort-order"
                        label={labels.sort_order}
                        hint={hints.sort_order}
                        error={form.errors.sort_order}
                    >
                        <FormInput
                            id="service-sort-order"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(value) =>
                                form.setData('sort_order', value)
                            }
                        />
                    </Field>

                    <div className="flex items-center justify-between gap-4 border-t border-hairline pt-4">
                        <Label htmlFor="service-active">
                            {labels.is_active}
                        </Label>
                        <Switch
                            id="service-active"
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked)
                            }
                        />
                    </div>

                    {!form.data.is_active ? (
                        <Alert>
                            <Info aria-hidden="true" />
                            <AlertDescription>
                                {copy.validation_callout}
                            </AlertDescription>
                        </Alert>
                    ) : null}
                </aside>
            </div>
        </form>
    );
}
