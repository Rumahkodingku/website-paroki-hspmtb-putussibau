import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Church } from 'lucide-react';
import { Field, FormLabel } from '@/components/form-fields';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { superAdmin } from '@/routes/install';
import { validate } from '@/routes/install/application';
import StepShell from '../components/step-shell';
import type { ApplicationMeta, ApplicationValues } from '../types';

type Props = {
    values: ApplicationValues;
    meta: ApplicationMeta;
};

export default function Application({ values, meta }: Props) {
    const form = useForm({
        parish_name: values.parish_name ?? '',
        parish_short_name: values.parish_short_name ?? '',
        tagline: values.tagline ?? '',
        short_description: values.short_description ?? '',
        patron_saint: values.patron_saint ?? '',
        app_name: meta.appName ?? '',
        app_url: meta.appUrl ?? '',
        timezone: meta.timezone ?? 'Asia/Pontianak',
        locale: meta.locale ?? 'id',
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
                eyebrow="Langkah 5 dari 7"
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
                            {form.processing ? (
                                <Spinner aria-hidden="true" />
                            ) : (
                                <Church aria-hidden="true" />
                            )}
                            Lanjutkan ke Ringkasan
                            <ArrowRight aria-hidden="true" />
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
                                aria-invalid={Boolean(form.errors.parish_name)}
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
                                aria-invalid={Boolean(form.errors.patron_saint)}
                                className="h-11"
                                placeholder="Santa Perawan Maria"
                            />
                        </Field>
                    </div>

                    <div className="flex flex-col gap-2">
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
                            {form.data.short_description.length}/500 karakter
                        </Text>
                    </div>

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
                    </div>
                </form>
            </StepShell>
        </>
    );
}
