import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { Field, FormLabel } from '@/components/form-fields';
import PasswordInput from '@/components/password-input';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { database } from '@/routes/install';
import { validate } from '@/routes/install/super-admin';
import StepShell from '../components/step-shell';

type Props = {
    values: {
        name: string;
        email: string;
    };
};

export default function SuperAdmin({ values }: Props) {
    const form = useForm({
        name: values.name,
        email: values.email,
        password: '',
        password_confirmation: '',
    });

    const setField =
        (key: keyof typeof form.data) =>
        (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
            form.setData(key, event.target.value);

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(validate().url, {
            preserveScroll: true,
            onSuccess: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Akun Super Admin" />

            <StepShell
                eyebrow="Langkah 4 dari 8"
                title="Akun Super Admin"
                description="Akun ini dibuat pada langkah terakhir. Role Super Admin ditetapkan otomatis; tidak ada pilihan role pada wizard."
                footer={
                    <>
                        <Button variant="ghost" asChild className="text-sm">
                            <Link href={database()}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali
                            </Link>
                        </Button>

                        <Button
                            type="submit"
                            form="super-admin-form"
                            disabled={form.processing}
                            className="text-sm"
                            size="lg"
                        >
                            Lanjutkan
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
                    id="super-admin-form"
                    onSubmit={submit}
                    className="flex flex-col gap-5"
                    noValidate
                >
                    <Field
                        id="name"
                        label="Nama lengkap"
                        isRequired
                        error={form.errors.name}
                    >
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            value={form.data.name}
                            onChange={setField('name')}
                            autoComplete="name"
                            autoFocus
                            required
                            maxLength={100}
                            aria-invalid={Boolean(form.errors.name)}
                            className="h-11"
                            placeholder="Nama lengkap administrator"
                        />
                    </Field>

                    <Field
                        id="email"
                        label="Email"
                        isRequired
                        error={form.errors.email}
                        hint="Email ini dipakai untuk masuk ke panel administrasi."
                    >
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            value={form.data.email}
                            onChange={setField('email')}
                            autoComplete="email"
                            inputMode="email"
                            required
                            maxLength={255}
                            aria-invalid={Boolean(form.errors.email)}
                            className="h-11"
                            placeholder="nama@paroki.example.org"
                        />
                    </Field>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <FormLabel
                                htmlFor="password"
                                isRequired
                                error={form.errors.password}
                            >
                                Password
                            </FormLabel>

                            <PasswordInput
                                placeholder="Masukkan password"
                                id="password"
                                name="password"
                                value={form.data.password}
                                onChange={setField('password')}
                                autoComplete="new-password"
                                required
                                aria-invalid={Boolean(form.errors.password)}
                                className="h-11"
                            />
                        </div>

                        <div className="flex flex-col gap-2">
                            <FormLabel
                                htmlFor="password_confirmation"
                                isRequired
                                error={form.errors.password_confirmation}
                            >
                                Konfirmasi password
                            </FormLabel>

                            <PasswordInput
                                placeholder="Masukkan konfirmasi password"
                                id="password_confirmation"
                                name="password_confirmation"
                                value={form.data.password_confirmation}
                                onChange={setField('password_confirmation')}
                                autoComplete="new-password"
                                required
                                aria-invalid={Boolean(
                                    form.errors.password_confirmation,
                                )}
                                className="h-11"
                            />
                        </div>
                    </div>

                    <Text as="p" variant="fine-print" color="muted">
                        Password tidak pernah ditampilkan kembali setelah
                        disimpan. Catat di tempat yang aman.
                    </Text>
                </form>
            </StepShell>
        </>
    );
}
