// Components
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import { Text } from '@/components/text';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { email } from '@/routes/password';

/**
 * Fortify's password reset view. It receives `status` only after a link has
 * been sent, and the component renders a confirmation instead of the form when
 * it is present.
 *
 * @see https://laravel.com/docs/fortify
 */
type Props = {
    status?: string;
};

export default function ForgotPassword({ status }: Props) {
    return (
        <>
            <Head title="Lupa Kata Sandi" />

            {status && (
                <Text
                    as="p"
                    variant="caption"
                    weight="medium"
                    color="brand"
                    align="center"
                    className="mb-4"
                >
                    {status}
                </Text>
            )}

            <div className="space-y-6">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="email@example.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button
                                    className="w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    Email password reset link
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                {/*
                    The line inherits its size from the Text, and the TextLink
                    inside it inherits too - TextLink carries no size of its own,
                    so a caller that wanted one had been reaching for a Tailwind
                    scale step to add to the anchor. `caption` is the same 14px
                    this was at as text-sm.
                */}
                <Text
                    as="p"
                    variant="caption"
                    color="muted"
                    align="center"
                    className="space-x-1"
                >
                    <span>Or, return to</span>
                    <TextLink href={login()}>log in</TextLink>
                </Text>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Lupa Kata Sandi',
    description: 'Enter your email to receive a password reset link',
};
