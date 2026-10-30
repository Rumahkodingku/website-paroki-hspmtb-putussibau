import { Form, Head } from '@inertiajs/react';
import { FormLabel } from '@/components/form-fields';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import { LockKeyhole } from 'lucide-react';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="Konfirmasi Kata Sandi" />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <div className="flex items-center justify-between gap-3">
                                <FormLabel htmlFor="password" isRequired>
                                    Kata sandi
                                </FormLabel>
                            </div>

                            <div className="relative">
                                <LockKeyhole
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-4 z-10 size-4.5 -translate-y-1/2 text-muted-foreground"
                                />

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Masukkan kata sandi"
                                    aria-invalid={Boolean(errors.password)}
                                    aria-describedby={
                                        errors.password
                                            ? 'password-error'
                                            : undefined
                                    }
                                    className="h-12 pr-11 pl-11"
                                />
                            </div>

                            {errors.password && (
                                <div id="password-error">
                                    <InputError message={errors.password} />
                                </div>
                            )}
                        </div>

                        <div className="flex items-center">
                            <Button
                                className="w-full text-sm"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                Konfirmasi Password
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Konfirmasi Kata Sandi',
    description:
        'Ini adalah area aman dalam aplikasi. Harap konfirmasi kata sandi Anda sebelum melanjutkan.',
};
