import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as historyIndex } from '@/routes/parish-histories';
import { update as updateHistory } from '@/routes/parish-histories';
import {
    HistoryItemForm,
    historyItemToFormData,
} from './components/history-item-form';
import type { ParishHistoryItemRow } from '../../types';

type Props = {
    item: ParishHistoryItemRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Edit({ item, labels, hints }: Props) {
    return (
        <>
            <Head title={`Ubah: ${item.title}`} />

            <div className="p-6">
                <Heading
                    title="Ubah Peristiwa"
                    description={item.title}
                    variant="default"
                />

                <HistoryItemForm
                    initial={historyItemToFormData(item)}
                    currentImageUrl={item.image_url}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) =>
                        form.submit(updateHistory({ history_item: item.id }))
                    }
                    onCancel={() => router.visit(historyIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Sejarah Paroki' },
        { title: 'Ubah' },
    ],
};
