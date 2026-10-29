import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Database as DatabaseIcon,
    Info,
} from 'lucide-react';
import { useState } from 'react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Field } from '@/components/form-fields';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PasswordInput from '@/components/password-input';
import { requirements } from '@/routes/install';
import { test } from '@/routes/install/database';
import StepShell from '../components/step-shell';
import type { DatabaseInfo } from '../types';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

type Props = {
    database: DatabaseInfo;
    supportedDrivers?: string[];
    verifiedAt: string | null;
};

type FormData = {
    driver: string;
    host: string;
    port: number | string;
    database: string;
    username: string;
    password: string;
};

export default function DatabaseStep({
    database,
    supportedDrivers,
    verifiedAt,
}: Props) {
    const drivers =
        supportedDrivers && supportedDrivers.length > 0
            ? supportedDrivers
            : [database.driver || 'mysql'];

    const form = useForm<FormData>({
        driver: database.driver || 'mysql',
        host: database.host ?? '',
        port: database.port ?? 3306,
        database: database.database ?? '',
        username: database.username ?? '',
        password: '',
    });

    const [processing, setProcessing] = useState(false);

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        setProcessing(true);

        form.post(test().url, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <>
            <Head title="Konfigurasi Database" />

            <StepShell
                eyebrow="Langkah 3 dari 7"
                title="Koneksi database"
                description="Isi kredensial database target. Wizard menguji koneksi dan memastikan database masih kosong sebelum melanjutkan."
                footer={
                    <>
                        <Button variant="ghost" asChild className="text-sm">
                            <Link href={requirements()}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali
                            </Link>
                        </Button>

                        <form onSubmit={submit}>
                            <Button
                                type="submit"
                                disabled={processing || !form.data.driver}
                                className="text-sm"
                                size="lg"
                            >
                                {processing ? (
                                    <Spinner aria-hidden="true" />
                                ) : (
                                    <DatabaseIcon aria-hidden="true" />
                                )}
                                Uji Koneksi dan Lanjutkan
                                <ArrowRight aria-hidden="true" />
                            </Button>
                        </form>
                    </>
                }
            >
                <form
                    onSubmit={submit}
                    className="flex flex-col gap-5"
                    noValidate
                >
                    {verifiedAt ? (
                        <div className="flex items-start gap-3 rounded-lg border border-emerald-600/30 bg-emerald-500/10 px-4 py-3">
                            <CheckCircle2
                                aria-hidden="true"
                                className="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                            />
                            <Text
                                as="p"
                                variant="body-sm"
                                className="text-emerald-900 dark:text-emerald-200"
                            >
                                Koneksi database sudah diverifikasi pada sesi
                                ini.
                            </Text>
                        </div>
                    ) : null}

                    {form.errors.database ? (
                        <div
                            role="alert"
                            className="rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3"
                        >
                            <Text
                                as="p"
                                variant="body-sm"
                                className="text-primary-on-surface"
                            >
                                {form.errors.database}
                            </Text>
                        </div>
                    ) : null}

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="driver"
                            label="Driver"
                            isRequired
                            error={form.errors.driver}
                        >
                            <Select
                                value={form.data.driver}
                                onValueChange={(value) =>
                                    form.setData('driver', value)
                                }
                            >
                                <SelectTrigger
                                    id="driver"
                                    className="h-11 w-full border-border bg-background py-5 font-normal shadow-none focus-visible:border-primary"
                                >
                                    <SelectValue placeholder="Pilih driver" />
                                </SelectTrigger>
                                <SelectContent>
                                    {drivers.map((driver) => (
                                        <SelectItem key={driver} value={driver}>
                                            {driver}
                                        </SelectItem>
                                    ))}
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
                                required
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
                                required
                                className="h-11"
                                placeholder="3306"
                            />
                        </Field>

                        <Field
                            id="database"
                            label="Nama database"
                            isRequired
                            error={form.errors.database}
                        >
                            <Input
                                id="database"
                                name="database"
                                value={form.data.database}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) =>
                                    form.setData('database', event.target.value)
                                }
                                required
                                className="h-11"
                                placeholder="website_paroki_hspmtb"
                            />
                        </Field>

                        <Field
                            id="username"
                            label="Pengguna"
                            isRequired
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
                                required
                                className="h-11"
                                placeholder="root"
                            />
                        </Field>

                        <Field
                            id="password"
                            label="Password"
                            error={form.errors.password}
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                value={form.data.password}
                                onChange={(
                                    event: React.ChangeEvent<HTMLInputElement>,
                                ) =>
                                    form.setData('password', event.target.value)
                                }
                                className="h-11"
                                placeholder="Password database"
                            />
                        </Field>
                    </div>

                    <Alert className="rounded-xl border-0 border-amber-500/30 bg-amber-500/10 px-4 py-3 text-amber-900 dark:text-amber-200">
                        <Info
                            aria-hidden="true"
                            className="size-4 text-muted-foreground"
                        />

                        <AlertTitle className="mb-1 font-bold">
                            Mengubah kredensial
                        </AlertTitle>

                        <AlertDescription className="text-sm leading-relaxed text-muted-foreground">
                            Kredensial disimpan sementara di sesi installer dan
                            ditulis ke <span className="font-mono">.env</span>{' '}
                            saat instalasi selesai. Pastikan database target
                            baru dan kosong.
                        </AlertDescription>
                    </Alert>
                </form>
            </StepShell>
        </>
    );
}
