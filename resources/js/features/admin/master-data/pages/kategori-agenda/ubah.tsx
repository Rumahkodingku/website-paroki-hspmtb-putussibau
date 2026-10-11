import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as eventCategoriesIndex,
    update as updateEventCategory,
} from '@/routes/event-categories';
import type { FormMeta } from '@/types/news-agenda';
import {
    EventCategoryForm,
    type EventCategoryFormInstance,
} from './components/event-category-form';
import type { EventCategoryRow } from '../../types/event-category';

type Props = {
    eventCategory: EventCategoryRow;
} & Pick<FormMeta, 'labels' | 'hints'>;

export default function Edit({ eventCategory, labels, hints }: Props) {
    return (
        <>
            <Head title="Ubah Kategori Agenda" />

            <div className="p-6">
                <Heading
                    title="Ubah Kategori Agenda"
                    description={`Lengkapi form untuk kategori ${eventCategory.name}.`}
                />

                <EventCategoryForm
                    initial={{
                        name: eventCategory.name,
                        slug: eventCategory.slug,
                        color: eventCategory.color,
                    }}
                    labels={labels}
                    hints={hints}
                    submitLabel="Simpan perubahan"
                    onSubmit={(form: EventCategoryFormInstance) =>
                        form.submit(updateEventCategory(eventCategory.id))
                    }
                    onCancel={() => router.visit(eventCategoriesIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Kategori Agenda', href: eventCategoriesIndex() },
        { title: 'Ubah' },
    ],
};
