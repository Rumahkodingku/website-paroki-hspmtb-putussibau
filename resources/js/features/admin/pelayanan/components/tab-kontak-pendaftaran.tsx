import type { ChangeEvent } from 'react';
import { ExternalLink } from 'lucide-react';
import { Field, FormInput, FormTextarea } from '@/components/form-fields';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { ServiceFormInstance } from './service-form-instance';

type Props = {
    form: ServiceFormInstance;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    onSubmit: (event: ChangeEvent) => void;
};

function isValidGoogleForm(url: string): boolean {
    if (url.trim() === '') {
        return false;
    }

    try {
        const parsed = new URL(url);

        if (parsed.protocol !== 'https:') {
            return false;
        }

        if (parsed.username !== '' || parsed.password !== '') {
            return false;
        }

        if (parsed.hostname === 'docs.google.com') {
            return parsed.pathname.startsWith('/forms/');
        }

        if (parsed.hostname === 'forms.gle') {
            return parsed.pathname.replace(/\//g, '') !== '';
        }

        return false;
    } catch {
        return false;
    }
}

export function TabKontakPendaftaran({
    form,
    labels,
    hints,
    copy,
    onSubmit,
}: Props) {
    const formUrlValid = isValidGoogleForm(form.data.form_url);

    return (
        <form noValidate onSubmit={onSubmit} className="space-y-6">
            <div className="space-y-1">
                <Text as="h3" variant="h3">
                    Kontak & Pendaftaran
                </Text>
                <Text as="p" variant="body-sm" color="muted">
                    Lengkapi informasi kontak dan pendaftaran pelayanan ini.
                    Informasi ini akan ditampilkan di halaman pelayanan
                </Text>
            </div>

            <div className="grid gap-8">
                <section className="space-y-5">
                    {/* <div className="space-y-1">
                        <Text as="h3" variant="h3">
                            {copy.registration_title}
                        </Text>
                        <Text as="p" variant="body-sm" color="muted">
                            {copy.registration_description}
                        </Text>
                    </div> */}

                    <Field
                        id="service-form-url"
                        label={labels.form_url}
                        hint={hints.form_url}
                        error={form.errors.form_url}
                    >
                        <FormInput
                            id="service-form-url"
                            inputMode="url"
                            value={form.data.form_url}
                            onChange={(value) =>
                                form.setData('form_url', value)
                            }
                            placeholder="https://docs.google.com/forms/..."
                        />
                    </Field>

                    {formUrlValid ? (
                        <Button
                            asChild
                            type="button"
                            variant="outline"
                            size="sm"
                        >
                            <a
                                href={form.data.form_url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                {copy.open_link}
                            </a>
                        </Button>
                    ) : (
                        <Text as="p" variant="body-sm" color="muted">
                            {form.data.form_url === ''
                                ? copy.not_using_form
                                : copy.invalid_url_notice}
                        </Text>
                    )}

                    <Field
                        id="service-button-label"
                        label={labels.form_button_label}
                        hint={hints.form_button_label}
                        error={form.errors.form_button_label}
                    >
                        <FormInput
                            id="service-button-label"
                            value={form.data.form_button_label}
                            onChange={(value) =>
                                form.setData('form_button_label', value)
                            }
                        />
                    </Field>

                    <div className="flex items-center justify-between gap-4">
                        <Label htmlFor="service-form-open">
                            {labels.is_form_open}
                        </Label>
                        <Switch
                            id="service-form-open"
                            checked={form.data.is_form_open}
                            disabled={
                                form.data.form_url === '' ||
                                form.data.is_emergency
                            }
                            onCheckedChange={(checked) =>
                                form.setData('is_form_open', checked)
                            }
                        />
                    </div>

                    {form.data.is_form_open ? (
                        <Field
                            id="service-closed-message"
                            label={labels.form_closed_message}
                            hint={hints.form_closed_message}
                            error={form.errors.form_closed_message}
                        >
                            <FormTextarea
                                id="service-closed-message"
                                value={form.data.form_closed_message}
                                onChange={(value) =>
                                    form.setData('form_closed_message', value)
                                }
                                rows={2}
                            />
                        </Field>
                    ) : null}
                </section>

                <div className="space-y-5">
                    <section className="space-y-5">
                        {/* <div className="space-y-1">
                            <Text as="h3" variant="h3">
                                {copy.contact_person_title}
                            </Text>
                            <Text as="p" variant="body-sm" color="muted">
                                {copy.contact_person_description}
                            </Text>
                        </div> */}

                        <Field
                            id="service-contact-name"
                            label={labels.contact_name}
                            hint={hints.contact_name}
                            error={form.errors.contact_name}
                        >
                            <FormInput
                                id="service-contact-name"
                                value={form.data.contact_name}
                                onChange={(value) =>
                                    form.setData('contact_name', value)
                                }
                            />
                        </Field>

                        <Field
                            id="service-contact-phone"
                            label={labels.contact_phone}
                            hint={hints.contact_phone}
                            error={form.errors.contact_phone}
                        >
                            <FormInput
                                id="service-contact-phone"
                                inputMode="numeric"
                                value={form.data.contact_phone}
                                onChange={(value) =>
                                    form.setData('contact_phone', value)
                                }
                                placeholder={copy.placeholder_phone}
                            />
                        </Field>
                    </section>

                    <section className="space-y-4 rounded-lg border border-border bg-muted p-5">
                        <div className="flex items-center justify-between gap-4">
                            <Label htmlFor="service-emergency">
                                {labels.is_emergency}
                            </Label>
                            <Switch
                                id="service-emergency"
                                checked={form.data.is_emergency}
                                onCheckedChange={(checked) => {
                                    form.setData({
                                        is_emergency: checked,
                                        form_url: checked
                                            ? ''
                                            : form.data.form_url,
                                        is_form_open: checked
                                            ? false
                                            : form.data.is_form_open,
                                    });
                                }}
                            />
                        </div>

                        <Text as="p" variant="body-sm" color="muted">
                            {hints.is_emergency}
                        </Text>

                        {form.data.is_emergency ? (
                            <Text as="p" variant="body-sm" color="muted">
                                {copy.emergency_no_url_notice}
                            </Text>
                        ) : null}
                    </section>

                    <section className="space-y-2">
                        <Text as="h3" variant="h3">
                            {copy.notes_title}
                        </Text>
                        <ul className="list-disc space-y-1 pl-5">
                            <li>
                                <Text as="span" variant="body-sm" color="muted">
                                    {copy.note_form_optional}
                                </Text>
                            </li>
                            <li>
                                <Text as="span" variant="body-sm" color="muted">
                                    {copy.note_test_link}
                                </Text>
                            </li>
                            <li>
                                <Text as="span" variant="body-sm" color="muted">
                                    {copy.note_manual_sync}
                                </Text>
                            </li>
                            <li>
                                <Text as="span" variant="body-sm" color="muted">
                                    {copy.note_contact_consent}
                                </Text>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </form>
    );
}
