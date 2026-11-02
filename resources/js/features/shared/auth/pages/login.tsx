import { Form, Head } from '@inertiajs/react';
import { toast } from 'sonner';
import { FormLabel } from '@/components/form-fields';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Text } from '@/components/text';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { LockKeyhole, Mail } from 'lucide-react';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

const CREDENTIALS_ERROR = 'Email atau password salah';
const CREDENTIALS_DESCRIPTION =
    'Periksa kembali email dan password Anda, lalu coba lagi.';

function showLoginErrorToast(errors: Record<string, string>): void {
    const serverMessage = errors.email ?? errors.password;
    const description =
        serverMessage && serverMessage !== CREDENTIALS_ERROR
            ? serverMessage
            : CREDENTIALS_DESCRIPTION;

    toast.error(CREDENTIALS_ERROR, { description });
}

function showLoginThrottleToast(): void {
    toast.error('Terlalu banyak percobaan masuk', {
        description: 'Silakan coba lagi dalam beberapa saat.',
    });
}

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
                noValidate
                onError={showLoginErrorToast}
                onHttpException={(response) => {
                    if (response.status !== 429) {
                        return;
                    }

                    showLoginThrottleToast();

                    return false;
                }}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-5">
                        {/* Email */}
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

                        {/* Password */}
                        <div className="grid gap-2">
                            <div className="flex items-center justify-between gap-3">
                                <FormLabel htmlFor="password" isRequired>
                                    Kata sandi
                                </FormLabel>

                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        tabIndex={5}
                                        className="hover:text- text-xs font-medium text-primary underline-offset-4 transition-colors hover:underline focus-visible:underline"
                                    >
                                        Lupa kata sandi?
                                    </TextLink>
                                )}
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

                        {/* Remember me */}
                        <div className="flex items-center gap-3 pt-1">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                                className="size-4 rounded"
                            />

                            <Text
                                as="label"
                                htmlFor="remember"
                                variant="body-sm"
                                className="cursor-pointer"
                            >
                                Ingat saya
                            </Text>
                        </div>

                        {/* Submit button */}
                        <Button
                            type="submit"
                            className="mt-2 h-12 w-full cursor-pointer bg-primary text-sm font-semibold text-primary-foreground shadow-none transition-colors hover:bg-primary-hover focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing ? (
                                <>
                                    <Spinner />
                                    <Text
                                        as="span"
                                        variant="body-sm"
                                        weight="medium"
                                    >
                                        Memproses...
                                    </Text>
                                </>
                            ) : (
                                <Text
                                    as="span"
                                    variant="body-sm"
                                    weight="medium"
                                >
                                    Masuk ke Panel Admin
                                </Text>
                            )}
                        </Button>

                        {/* Status message */}
                        {status && (
                            <div
                                role="status"
                                className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                            >
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="foreground"
                                >
                                    {status}
                                </Text>
                            </div>
                        )}
                    </div>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Masuk ke akun Anda',
    description: 'Masukkan email dan kata sandi Anda di bawah ini',
};
