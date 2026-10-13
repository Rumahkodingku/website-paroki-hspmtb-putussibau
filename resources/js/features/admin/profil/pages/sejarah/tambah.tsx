import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as historyIndex,
    store as storeHistory,
} from '@/routes/parish-histories';
import {
    emptyHistoryItemFormData,
    HistoryItemForm,
} from './components/history-item-form';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Create({ labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Peristiwa Sejarah" />

            <div className="p-6">
                <Heading
                    title="Tambah Peristiwa"
                    description="Peristiwa baru pada linimasa sejarah paroki."
                    variant="default"
                />

                <HistoryItemForm
                    initial={emptyHistoryItemFormData()}
                    currentImageUrl={null}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) => form.submit(storeHistory())}
                    onCancel={() => router.visit(historyIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Sejarah Paroki' },
        { title: 'Tambah' },
    ],
};
