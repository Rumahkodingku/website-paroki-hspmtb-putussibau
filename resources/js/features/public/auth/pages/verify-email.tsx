// Components
import { Form, Head } from '@inertiajs/react';
import { Text } from '@/components/text';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

/**
 * Fortify's email verification notice. `status` is absent until a verification
 * link has been requested again, which is what decides between the prompt and
 * the confirmation.
 *
 * @see https://laravel.com/docs/fortify
 */
type Props = {
    status?: string;
};

export default function VerifyEmail({ status }: Props) {
    return (
        <>
            <Head title="Verifikasi Email" />

            {status === 'verification-link-sent' && (
                <Text
                    as="p"
                    variant="caption"
                    weight="medium"
                    color="brand"
                    align="center"
                    className="mb-4"
                >
                    A new verification link has been sent to the email address
                    you provided during registration.
                </Text>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Resend verification email
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-caption"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verifikasi Email',
    description:
        'Please verify your email address by clicking on the link we just emailed to you.',
};
