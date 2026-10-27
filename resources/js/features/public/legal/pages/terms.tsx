import { LegalPage } from '../components/legal-page';

type Props = {
    content: string | null;
};

export default function Terms({ content }: Props) {
    return (
        <LegalPage
            title="Syarat & Ketentuan"
            description="Syarat dan ketentuan penggunaan situs Paroki HSPMTB Putussibau."
            content={content}
        />
    );
}
