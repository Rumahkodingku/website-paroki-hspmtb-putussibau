import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as eventCategoriesIndex,
    store as storeEventCategory,
} from '@/routes/event-categories';
import type { FormMeta } from '@/types/news-agenda';
import {
    emptyEventCategoryFormData,
    EventCategoryForm,
    type EventCategoryFormInstance,
} from './components/event-category-form';

type Props = Pick<FormMeta, 'labels' | 'hints'>;

export default function Create({ labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Kategori Agenda" />

            <div className="p-6">
                <Heading
                    title="Tambah Kategori Agenda"
                    description="Kelompokkan kegiatan agar mudah dicari di kalender."
                />

                <EventCategoryForm
                    initial={emptyEventCategoryFormData()}
                    labels={labels}
                    hints={hints}
                    submitLabel="Simpan kategori"
                    onSubmit={(form: EventCategoryFormInstance) =>
                        form.submit(storeEventCategory())
                    }
                    onCancel={() => router.visit(eventCategoriesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Kategori Agenda', href: eventCategoriesIndex() },
        { title: 'Tambah' },
    ],
};
