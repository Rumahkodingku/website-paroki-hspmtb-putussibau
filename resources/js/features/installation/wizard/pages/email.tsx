import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Mail } from 'lucide-react';
import { useState } from 'react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Field } from '@/components/form-fields';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { database } from '@/routes/install';
import { test, validate } from '@/routes/install/email';
import StepShell from '../components/step-shell';
import type { EmailValues } from '../types';

type Props = {
    values: EmailValues;
};

type FormData = {
    mailer: string;
    host: string;
    port: number | string;
    username: string;
    password: string;
    from_address: string;
    from_name: string;
};

export default function Email({ values }: Props) {
    const form = useForm<FormData>({
        mailer: values.mailer ?? 'smtp',
        host: values.host ?? '127.0.0.1',
        port: values.port ?? 1025,
        username: values.username ?? '',
        password: values.password ?? '',
        from_address: values.from_address ?? 'noreply@paroki-hspmtb.local',
        from_name: values.from_name ?? '',
    });

    const [testing, setTesting] = useState(false);
    const [saving, setSaving] = useState(false);
    const [testOpen, setTestOpen] = useState(false);
    const [testRecipient, setTestRecipient] = useState('');

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setSaving(true);
        form.post(validate().url, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const isSmtp = form.data.mailer === 'smtp';

    return (
        <>
            <Head title="Konfigurasi Email" />

            <StepShell
                eyebrow="Langkah 4 dari 8"
                title="Konfigurasi email"
                description="Isi pengaturan SMTP untuk pengiriman email (reset kata sandi, verifikasi email). Nilai default sesuai Mailpit untuk pengembangan lokal."
                footer={
                    <>
                        <Button variant="ghost" asChild className="text-sm">
                            <Link href={database()}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali
                            </Link>
                        </Button>

                        <div className="flex items-center gap-2">
                            <Dialog open={testOpen} onOpenChange={setTestOpen}>
                                <DialogTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="text-sm"
                                    >
                                        <Mail aria-hidden="true" />
                                        Test kirim email
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>
                                            Test kirim email
                                        </DialogTitle>
                                        <DialogDescription>
                                            Masukkan alamat email penerima uji.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div className="grid gap-2">
                                        <Field
                                            id="test-recipient"
                                            label="Pengguna"
                                            isRequired
                                        >
                                            <Input
                                                id="test-recipient"
                                                type="email"
                                                value={testRecipient}
                                                onChange={(
                                                    event: React.ChangeEvent<HTMLInputElement>,
                                                ) =>
                                                    setTestRecipient(
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="email@contoh.com"
                                                className="h-11"
                                            />
                                        </Field>
                                    </div>

                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            className="text-sm"
                                            disabled={testing || !testRecipient}
                                            onClick={() => {
                                                setTesting(true);
                                                router.post(
                                                    test().url,
                                                    {
                                                        ...form.data,
                                                        to: testRecipient,
                                                    },
                                                    {
                                                        preserveScroll: true,
                                                        onFinish: () =>
                                                            setTesting(false),
                                                        onSuccess: () =>
                                                            setTestOpen(false),
                                                        onError: () =>
                                                            setTestOpen(false),
                                                    },
                                                );
                                            }}
                                        >
                                            {testing ? (
                                                <Spinner aria-hidden="true" />
                                            ) : (
                                                <Mail aria-hidden="true" />
                                            )}
                                            Kirim email uji
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>

                            <Button
                                type="submit"
                                form="email-form"
                                disabled={saving}
                                className="text-sm"
                                size="lg"
                            >
                                Lanjutkan
                                {saving ? (
                                    <Spinner aria-hidden="true" />
                                ) : (
                                    <ArrowRight aria-hidden="true" />
                                )}
                            </Button>
                        </div>
                    </>
                }
            >
                <form
                    id="email-form"
                    onSubmit={submit}
                    className="flex flex-col gap-5"
                    noValidate
                >
                    {(form.errors as Record<string, string>).email ? (
                        <div
                            role="alert"
                            className="rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3"
                        >
                            <Text
                                as="p"
                                variant="body-sm"
                                className="text-primary-on-surface"
                            >
                                {(form.errors as Record<string, string>).email}
                            </Text>
                        </div>
                    ) : null}

                    <div className="grid gap-5 sm:grid-cols-3">
                        <Field
                            id="mailer"
                            label="Mailer"
                            isRequired
                            error={form.errors.mailer}
                        >
                            <Select
                                value={form.data.mailer}
                                onValueChange={(value) =>
                                    form.setData('mailer', value)
                                }
                            >
                                <SelectTrigger
                                    id="mailer"
                                    className="h-11 w-full border-border bg-background py-5 font-normal shadow-none focus-visible:border-primary"
                                >
                                    <SelectValue placeholder="Pilih mailer" />
                                </SelectTrigger>
                                <SelectContent className="uppercase">
                                    <SelectItem value="smtp">smtp</SelectItem>
                                    <SelectItem value="log">log</SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>

                        <Field
                            id="host"
                            label="Host"
                            isRequired
                            error={form.errors.host}
                        >
                            <Input
                                id="host"
                                name="host"
                                value={form.data.host}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) => form.setData('host', event.target.value)}
                                required={isSmtp}
                                disabled={!isSmtp}
                                className="h-11"
                                placeholder="127.0.0.1"
                            />
                        </Field>

                        <Field
                            id="port"
                            label="Port"
                            isRequired
                            error={form.errors.port}
                        >
                            <Input
                                id="port"
                                name="port"
                                type="number"
                                value={form.data.port}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) => form.setData('port', event.target.value)}
                                required={isSmtp}
                                disabled={!isSmtp}
                                className="h-11"
                                placeholder="1025"
                            />
                        </Field>
                    </div>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="username"
                            label="Pengguna (opsional)"
                            error={form.errors.username}
                        >
                            <Input
                                id="username"
                                name="username"
                                value={form.data.username}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) =>
                                    form.setData('username', event.target.value)
                                }
                                disabled={!isSmtp}
                                className="h-11"
                                placeholder="null"
                            />
                        </Field>

                        <Field
                            id="password"
                            label="Password (opsional)"
                            error={form.errors.password}
                        >
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                value={form.data.password}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) =>
                                    form.setData('password', event.target.value)
                                }
                                disabled={!isSmtp}
                                className="h-11"
                                placeholder="null"
                            />
                        </Field>

                        <Field
                            id="from_address"
                            label="Alamat pengirim"
                            isRequired
                            error={form.errors.from_address}
                        >
                            <Input
                                id="from_address"
                                name="from_address"
                                type="email"
                                value={form.data.from_address}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) =>
                                    form.setData(
                                        'from_address',
                                        event.target.value,
                                    )
                                }
                                required
                                className="h-11"
                                placeholder="noreply@paroki-hspmtb.local"
                            />
                        </Field>

                        <Field
                            id="from_name"
                            label="Nama pengirim"
                            isRequired
                            error={form.errors.from_name}
                        >
                            <Input
                                id="from_name"
                                name="from_name"
                                value={form.data.from_name}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) =>
                                    form.setData(
                                        'from_name',
                                        event.target.value,
                                    )
                                }
                                required
                                className="h-11"
                                placeholder="${APP_NAME}"
                            />
                        </Field>
                    </div>
                </form>
            </StepShell>
        </>
    );
}
