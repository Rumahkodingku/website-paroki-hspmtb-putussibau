import { LegalPage } from '../components/legal-page';

type Props = {
    content: string | null;
};

export default function Privacy({ content }: Props) {
    return (
        <LegalPage
            title="Kebijakan Privasi"
            description="Kebijakan privasi Paroki HSPMTB Putussibau."
            content={content}
        />
    );
}
