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
                    variant="body-sm"
                    weight="medium"
                    color="brand"
                    align="center"
                    className="mb-4"
                >
                    Tautan verifikasi baru telah dikirim ke alamat email yang
                    Anda berikan saat pendaftaran.
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
                            className="mx-auto block text-body-sm"
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
        'Verifikasi alamat email Anda dengan mengeklik tautan yang baru kami kirimkan.',
};
