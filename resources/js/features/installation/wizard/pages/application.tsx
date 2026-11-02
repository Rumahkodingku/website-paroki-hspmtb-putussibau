import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Field, FormLabel } from '@/components/form-fields';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { superAdmin } from '@/routes/install';
import { validate } from '@/routes/install/application';
import StepShell from '../components/step-shell';
import type { ApplicationMeta, ApplicationValues } from '../types';

type Props = {
    values: ApplicationValues;
    meta: ApplicationMeta;
};

function FormSection({
    title,
    description,
    defaultOpen = false,
    hasError = false,
    children,
}: {
    title: string;
    description: string;
    defaultOpen?: boolean;
    hasError?: boolean;
    children: React.ReactNode;
}) {
    const [open, setOpen] = useState(defaultOpen || hasError);

    useEffect(() => {
        if (hasError) {
            setOpen(true);
        }
    }, [hasError]);

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="group">
            <CollapsibleTrigger className="flex w-full items-center justify-between gap-4 py-2 text-left transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none">
                <div className="flex flex-col gap-0.5">
                    <span className="text-sm font-semibold">{title}</span>
                    <span className="text-xs text-muted-foreground">
                        {description}
                    </span>
                </div>
                <ChevronDown
                    className="size-5 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                    aria-hidden="true"
                />
            </CollapsibleTrigger>
            <CollapsibleContent className="border-t border-border py-4">
                {children}
            </CollapsibleContent>
        </Collapsible>
    );
}

export default function Application({ values, meta }: Props) {
    const form = useForm({
        parish_name: values.parish_name ?? '',
        parish_short_name: values.parish_short_name ?? '',
        tagline: values.tagline ?? '',
        short_description: values.short_description ?? '',
        motto_verse: values.motto_verse ?? '',
        patron_saint: values.patron_saint ?? '',
        contact_address: values.contact_address ?? '',
        contact_phone: values.contact_phone ?? '',
        contact_whatsapp: values.contact_whatsapp ?? '',
        contact_email: values.contact_email ?? '',
        maps_link: values.maps_link ?? '',
        social_facebook: values.social_facebook ?? '',
        social_instagram: values.social_instagram ?? '',
        social_youtube: values.social_youtube ?? '',
        social_whatsapp_channel: values.social_whatsapp_channel ?? '',
        app_name: meta.appName ?? '',
        app_url: meta.appUrl ?? '',
        timezone: meta.timezone ?? 'Asia/Pontianak',
        locale: meta.locale ?? 'id',
        environment: meta.environment ?? 'production',
    });

    const setField =
        (key: keyof typeof form.data) =>
        (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
            form.setData(key, event.target.value);

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(validate().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Identitas Paroki" />

            <StepShell
                eyebrow="Langkah 5 dari 8"
                title="Identitas paroki"
                description="Lima nilai berikut disimpan melalui Pengaturan Situs. Data opsional boleh dikosongkan; tidak ada nilai contoh yang diisi otomatis."
                footer={
                    <>
                        <Button variant="ghost" asChild className="text-sm">
                            <Link href={superAdmin()}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali
                            </Link>
                        </Button>

                        <Button
                            type="submit"
                            form="application-form"
                            className="text-sm"
                            size="lg"
                        >
                            Lanjutkan ke Ringkasan
                            {form.processing ? (
                                <Spinner aria-hidden="true" />
                            ) : (
                                <ArrowRight aria-hidden="true" />
                            )}
                        </Button>
                    </>
                }
            >
                <form
                    id="application-form"
                    onSubmit={submit}
                    className="flex flex-col gap-5"
                    noValidate
                >
                    <FormSection
                        title="Identitas Paroki"
                        description="Nama, tagline, patron santo, deskripsi, dan motto paroki."
                        defaultOpen
                        hasError={Boolean(
                            form.errors.parish_name ||
                            form.errors.parish_short_name ||
                            form.errors.tagline ||
                            form.errors.patron_saint ||
                            form.errors.short_description ||
                            form.errors.motto_verse,
                        )}
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                id="parish_name"
                                label="Nama paroki"
                                isRequired
                                error={form.errors.parish_name}
                            >
                                <Input
                                    id="parish_name"
                                    name="parish_name"
                                    value={form.data.parish_name}
                                    onChange={setField('parish_name')}
                                    autoFocus
                                    required
                                    maxLength={150}
                                    aria-invalid={Boolean(
                                        form.errors.parish_name,
                                    )}
                                    className="h-11"
                                    placeholder="Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau"
                                />
                            </Field>

                            <Field
                                id="parish_short_name"
                                label="Nama singkat"
                                isRequired
                                error={form.errors.parish_short_name}
                            >
                                <Input
                                    id="parish_short_name"
                                    name="parish_short_name"
                                    value={form.data.parish_short_name}
                                    onChange={setField('parish_short_name')}
                                    required
                                    maxLength={50}
                                    aria-invalid={Boolean(
                                        form.errors.parish_short_name,
                                    )}
                                    className="h-11"
                                    placeholder="HSPMTB"
                                />
                            </Field>

                            <Field
                                id="tagline"
                                label="Tagline"
                                error={form.errors.tagline}
                            >
                                <Input
                                    id="tagline"
                                    name="tagline"
                                    value={form.data.tagline}
                                    onChange={setField('tagline')}
                                    maxLength={150}
                                    aria-invalid={Boolean(form.errors.tagline)}
                                    className="h-11"
                                    placeholder="Melayani dengan hati"
                                />
                            </Field>

                            <Field
                                id="patron_saint"
                                label="Patron santo"
                                error={form.errors.patron_saint}
                            >
                                <Input
                                    id="patron_saint"
                                    name="patron_saint"
                                    value={form.data.patron_saint}
                                    onChange={setField('patron_saint')}
                                    maxLength={100}
                                    aria-invalid={Boolean(
                                        form.errors.patron_saint,
                                    )}
                                    className="h-11"
                                    placeholder="Santa Perawan Maria"
                                />
                            </Field>
                        </div>

                        <div className="mt-4 flex flex-col gap-2">
                            <FormLabel
                                htmlFor="short_description"
                                error={form.errors.short_description}
                            >
                                Deskripsi singkat
                            </FormLabel>

                            <Textarea
                                id="short_description"
                                name="short_description"
                                rows={4}
                                value={form.data.short_description}
                                onChange={setField('short_description')}
                                maxLength={500}
                                aria-invalid={Boolean(
                                    form.errors.short_description,
                                )}
                                className="min-h-24 resize-y border-border shadow-none focus-visible:border-primary"
                                placeholder="Deskripsi singkat paroki yang tampil di beranda."
                            />

                            <Text as="p" variant="fine-print" color="muted">
                                {form.data.short_description.length}/500
                                karakter
                            </Text>
                        </div>

                        <div className="mt-4 flex flex-col gap-2">
                            <FormLabel
                                htmlFor="motto_verse"
                                error={form.errors.motto_verse}
                            >
                                Motto / ayat
                            </FormLabel>

                            <Textarea
                                id="motto_verse"
                                name="motto_verse"
                                rows={3}
                                value={form.data.motto_verse}
                                onChange={setField('motto_verse')}
                                maxLength={500}
                                aria-invalid={Boolean(form.errors.motto_verse)}
                                className="min-h-20 resize-y border-border shadow-none focus-visible:border-primary"
                                placeholder="Contoh: Sebab di mana dua atau tiga orang berkumpul dalam nama-Ku, di situ Aku ada."
                            />

                            <Text as="p" variant="fine-print" color="muted">
                                {form.data.motto_verse.length}/500 karakter
                            </Text>
                        </div>
                    </FormSection>

                    <FormSection
                        title="Kontak"
                        description="Opsional. Alamat, telepon, WhatsApp, surel, dan tautan Google Maps paroki."
                        hasError={Boolean(
                            form.errors.contact_address ||
                            form.errors.contact_phone ||
                            form.errors.contact_whatsapp ||
                            form.errors.contact_email ||
                            form.errors.maps_link,
                        )}
                    >
                        <div className="flex flex-col gap-5">
                            <div className="flex flex-col gap-2">
                                <FormLabel
                                    htmlFor="contact_address"
                                    error={form.errors.contact_address}
                                >
                                    Alamat
                                </FormLabel>
                                <Textarea
                                    id="contact_address"
                                    name="contact_address"
                                    rows={3}
                                    value={form.data.contact_address}
                                    onChange={setField('contact_address')}
                                    maxLength={2048}
                                    aria-invalid={Boolean(
                                        form.errors.contact_address,
                                    )}
                                    className="min-h-20 resize-y border-border shadow-none focus-visible:border-primary"
                                    placeholder="Alamat sekretariat paroki"
                                />
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field
                                    id="contact_phone"
                                    label="Telepon"
                                    error={form.errors.contact_phone}
                                >
                                    <Input
                                        id="contact_phone"
                                        name="contact_phone"
                                        value={form.data.contact_phone}
                                        onChange={setField('contact_phone')}
                                        maxLength={32}
                                        aria-invalid={Boolean(
                                            form.errors.contact_phone,
                                        )}
                                        className="h-11"
                                        placeholder="0561-..."
                                    />
                                </Field>

                                <Field
                                    id="contact_whatsapp"
                                    label="No WhatsApp"
                                    error={form.errors.contact_whatsapp}
                                >
                                    <Input
                                        id="contact_whatsapp"
                                        name="contact_whatsapp"
                                        value={form.data.contact_whatsapp}
                                        onChange={setField('contact_whatsapp')}
                                        maxLength={32}
                                        aria-invalid={Boolean(
                                            form.errors.contact_whatsapp,
                                        )}
                                        className="h-11"
                                        placeholder="0812..."
                                    />
                                </Field>

                                <Field
                                    id="contact_email"
                                    label="Surel / Email"
                                    error={form.errors.contact_email}
                                >
                                    <Input
                                        id="contact_email"
                                        name="contact_email"
                                        type="email"
                                        value={form.data.contact_email}
                                        onChange={setField('contact_email')}
                                        maxLength={255}
                                        aria-invalid={Boolean(
                                            form.errors.contact_email,
                                        )}
                                        className="h-11"
                                        placeholder="paroki@example.org"
                                    />
                                </Field>

                                <Field
                                    id="maps_link"
                                    label="Tautan Full Google Maps"
                                    error={form.errors.maps_link}
                                >
                                    <Input
                                        id="maps_link"
                                        name="maps_link"
                                        type="url"
                                        value={form.data.maps_link}
                                        onChange={setField('maps_link')}
                                        maxLength={2048}
                                        aria-invalid={Boolean(
                                            form.errors.maps_link,
                                        )}
                                        className="h-11"
                                        placeholder="https://www.google.com/maps/..."
                                    />
                                </Field>
                            </div>
                        </div>
                    </FormSection>

                    <FormSection
                        title="Media Sosial"
                        description="Opsional. Tautan akun media sosial paroki."
                        hasError={Boolean(
                            form.errors.social_facebook ||
                            form.errors.social_instagram ||
                            form.errors.social_youtube ||
                            form.errors.social_whatsapp_channel,
                        )}
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                id="social_facebook"
                                label="Facebook"
                                error={form.errors.social_facebook}
                            >
                                <Input
                                    id="social_facebook"
                                    name="social_facebook"
                                    type="url"
                                    value={form.data.social_facebook}
                                    onChange={setField('social_facebook')}
                                    maxLength={255}
                                    aria-invalid={Boolean(
                                        form.errors.social_facebook,
                                    )}
                                    className="h-11"
                                    placeholder="https://facebook.com/..."
                                />
                            </Field>

                            <Field
                                id="social_instagram"
                                label="Instagram"
                                error={form.errors.social_instagram}
                            >
                                <Input
                                    id="social_instagram"
                                    name="social_instagram"
                                    type="url"
                                    value={form.data.social_instagram}
                                    onChange={setField('social_instagram')}
                                    maxLength={255}
                                    aria-invalid={Boolean(
                                        form.errors.social_instagram,
                                    )}
                                    className="h-11"
                                    placeholder="https://instagram.com/..."
                                />
                            </Field>

                            <Field
                                id="social_youtube"
                                label="Youtube"
                                error={form.errors.social_youtube}
                            >
                                <Input
                                    id="social_youtube"
                                    name="social_youtube"
                                    type="url"
                                    value={form.data.social_youtube}
                                    onChange={setField('social_youtube')}
                                    maxLength={255}
                                    aria-invalid={Boolean(
                                        form.errors.social_youtube,
                                    )}
                                    className="h-11"
                                    placeholder="https://youtube.com/..."
                                />
                            </Field>

                            <Field
                                id="social_whatsapp_channel"
                                label="Kanal WhatsApp"
                                error={form.errors.social_whatsapp_channel}
                            >
                                <Input
                                    id="social_whatsapp_channel"
                                    name="social_whatsapp_channel"
                                    type="url"
                                    value={form.data.social_whatsapp_channel}
                                    onChange={setField(
                                        'social_whatsapp_channel',
                                    )}
                                    maxLength={255}
                                    aria-invalid={Boolean(
                                        form.errors.social_whatsapp_channel,
                                    )}
                                    className="h-11"
                                    placeholder="https://whatsapp.com/channel/..."
                                />
                            </Field>
                        </div>
                    </FormSection>

                    <FormSection
                        title="Konfigurasi Aplikasi"
                        description="Nama aplikasi, URL, zona waktu, bahasa, dan environment."
                        hasError={Boolean(
                            form.errors.app_name ||
                            form.errors.app_url ||
                            form.errors.timezone ||
                            form.errors.locale ||
                            form.errors.environment,
                        )}
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                id="app_name"
                                label="Nama aplikasi"
                                isRequired
                                error={form.errors.app_name}
                            >
                                <Input
                                    id="app_name"
                                    name="app_name"
                                    value={form.data.app_name}
                                    onChange={setField('app_name')}
                                    required
                                    maxLength={100}
                                    aria-invalid={Boolean(form.errors.app_name)}
                                    className="h-11"
                                    placeholder="Website Paroki HSPMTB"
                                />
                            </Field>

                            <Field
                                id="app_url"
                                label="URL aplikasi"
                                isRequired
                                error={form.errors.app_url}
                            >
                                <Input
                                    id="app_url"
                                    name="app_url"
                                    type="url"
                                    value={form.data.app_url}
                                    onChange={setField('app_url')}
                                    required
                                    maxLength={255}
                                    aria-invalid={Boolean(form.errors.app_url)}
                                    className="h-11"
                                    placeholder="https://paroki.example.org"
                                />
                            </Field>

                            <Field
                                id="timezone"
                                label="Zona waktu"
                                isRequired
                                error={form.errors.timezone}
                            >
                                <Input
                                    id="timezone"
                                    name="timezone"
                                    value={form.data.timezone}
                                    onChange={setField('timezone')}
                                    required
                                    maxLength={64}
                                    aria-invalid={Boolean(form.errors.timezone)}
                                    className="h-11"
                                    placeholder="Asia/Pontianak"
                                />
                            </Field>

                            <Field
                                id="locale"
                                label="Bahasa"
                                isRequired
                                error={form.errors.locale}
                            >
                                <Input
                                    id="locale"
                                    name="locale"
                                    value={form.data.locale}
                                    onChange={setField('locale')}
                                    required
                                    maxLength={8}
                                    aria-invalid={Boolean(form.errors.locale)}
                                    className="h-11"
                                    placeholder="id"
                                />
                            </Field>

                            <Field
                                id="environment"
                                label="Environment"
                                isRequired
                                error={form.errors.environment}
                            >
                                <Select
                                    value={form.data.environment}
                                    onValueChange={(value) =>
                                        form.setData('environment', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="environment"
                                        className="h-11 w-full border-border bg-background py-5 font-normal shadow-none focus-visible:border-primary"
                                    >
                                        <SelectValue placeholder="Pilih environment" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="production">
                                            production
                                        </SelectItem>
                                        <SelectItem value="local">
                                            local
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>
                    </FormSection>
                </form>
            </StepShell>
        </>
    );
}
