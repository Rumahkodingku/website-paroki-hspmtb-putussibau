import { Form, Head } from '@inertiajs/react';
import { FormLabel } from '@/components/form-fields';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';
import { LockKeyhole, Mail } from 'lucide-react';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    return (
        <>
            <Head title="Atur Ulang Kata Sandi" />

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <FormLabel htmlFor="email" isRequired>
                                Email
                            </FormLabel>

                            <div className="relative">
                                <Mail
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-4 size-4.5 -translate-y-1/2 text-muted-foreground"
                                />

                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    value={email}
                                    autoComplete="email"
                                    placeholder="Masukkan email Anda"
                                    aria-invalid={Boolean(errors.email)}
                                    aria-describedby={
                                        errors.email ? 'email-error' : undefined
                                    }
                                    className="h-12 pr-4 pl-11"
                                />
                            </div>

                            {errors.email && (
                                <div id="email-error">
                                    <InputError message={errors.email} />
                                </div>
                            )}
                        </div>

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

                        <div className="grid gap-2">
                            <div className="flex items-center justify-between gap-3">
                                <FormLabel
                                    htmlFor="password_confirmation"
                                    isRequired
                                >
                                    Konfirmasi Kata sandi
                                </FormLabel>
                            </div>

                            <div className="relative">
                                <LockKeyhole
                                    aria-hidden="true"
                                    className="pointer-events-none absolute top-1/2 left-4 z-10 size-4.5 -translate-y-1/2 text-muted-foreground"
                                />

                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    tabIndex={2}
                                    autoComplete="password_confirmation"
                                    placeholder="Masukkan kata sandi"
                                    aria-invalid={Boolean(
                                        errors.password_confirmation,
                                    )}
                                    aria-describedby={
                                        errors.password_confirmation
                                            ? 'password-error'
                                            : undefined
                                    }
                                    className="h-12 pr-11 pl-11"
                                    passwordrules={passwordRules}
                                />
                            </div>

                            {errors.password_confirmation && (
                                <div id="password-error">
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>
                            )}
                        </div>

                        <Button
                            type="submit"
                            size="lg"
                            className="mt-4 w-full text-sm"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && <Spinner />}
                            Reset password
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Atur Ulang Kata Sandi',
    description: 'Silakan masukkan kata sandi baru Anda di bawah ini',
};
