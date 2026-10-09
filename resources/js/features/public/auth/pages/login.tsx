import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Text } from '@/components/text';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        /*
                                         * caption rather than the text-sm it
                                         * carried before: same 14px, but the
                                         * design system's tracking instead of
                                         * Tailwind's scale step. TextLink owns
                                         * its own colour and underline - it is
                                         * a link, not a piece of text - so only
                                         * the size comes from here.
                                         */
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-caption"
                                            tabIndex={5}
                                        >
                                            Forgot your password?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>

                        {/*
                            No sign-up link: PRD AUTH-R2 forbids public
                            registration. Accounts are created by a Super Admin.
                            See docs/DECISIONS.md D-19.
                        */}
                    </>
                )}
            </Form>

            {status && (
                /*
                 * A status message, not body copy. `caption` is the same 14px
                 * this was at as text-sm, and `brand` carries the dark override
                 * with it - see the colour variant's comment for why that pair
                 * is not left to the caller.
                 */
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
        </>
    );
}

Login.layout = {
    title: 'Masuk ke akun Anda',
    description: 'Enter your email and password below to log in',
};
