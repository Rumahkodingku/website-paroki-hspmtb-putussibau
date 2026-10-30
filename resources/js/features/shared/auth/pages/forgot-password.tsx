import { Form, Head } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, Mail } from 'lucide-react';
import { FormLabel } from '@/components/form-fields';
import InputError from '@/components/input-error';
import { Text } from '@/components/text';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import { email } from '@/routes/password';

type Props = {
    status?: string;
};

export default function ForgotPassword({ status }: Props) {
    return (
        <>
            <Head title="Lupa Kata Sandi" />

            {status && (
                <div
                    role="status"
                    className="mb-6 rounded-xl border border-border bg-muted/40 px-4 py-3"
                >
                    <Text
                        as="p"
                        variant="body-sm"
                        weight="medium"
                        color="foreground"
                    >
                        {status}
                    </Text>
                </div>
            )}

            <div className="space-y-6">
                <Form {...email.form()} className="flex flex-col gap-6">
                    {({ processing, errors }) => (
                        <>
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
                                        autoComplete="email"
                                        autoFocus
                                        placeholder="Masukkan email Anda"
                                        aria-invalid={Boolean(errors.email)}
                                        aria-describedby={
                                            errors.email
                                                ? 'email-error'
                                                : undefined
                                        }
                                        className="h-12 border-border bg-background pr-4 pl-11 shadow-none transition-colors placeholder:text-muted-foreground/70 focus-visible:ring-2"
                                    />
                                </div>

                                {errors.email && (
                                    <div id="email-error">
                                        <InputError message={errors.email} />
                                    </div>
                                )}
                            </div>

                            <div className="pt-1">
                                <Button
                                    type="submit"
                                    className="h-12 w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing ? (
                                        <>
                                            <LoaderCircle
                                                aria-hidden="true"
                                                className="size-4 animate-spin"
                                            />
                                            <Text
                                                as="span"
                                                variant="body-sm"
                                                weight="medium"
                                            >
                                                Mengirim tautan...
                                            </Text>
                                        </>
                                    ) : (
                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            weight="medium"
                                        >
                                            Kirim tautan reset kata sandi
                                        </Text>
                                    )}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="pt-5">
                    <Text as="p" variant="body-sm" color="muted" align="center">
                        Tidak jadi mengatur ulang kata sandi?
                    </Text>

                    <div className="mt-3 flex justify-center">
                        <TextLink
                            href={login()}
                            className="inline-flex items-center gap-2 text-xs font-medium transition-opacity hover:opacity-80"
                        >
                            <ArrowLeft aria-hidden="true" className="size-4" />
                            Kembali ke halaman masuk
                        </TextLink>
                    </div>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Lupa Kata Sandi',
    description:
        'Masukkan email Anda untuk menerima tautan pengaturan ulang kata sandi',
};
