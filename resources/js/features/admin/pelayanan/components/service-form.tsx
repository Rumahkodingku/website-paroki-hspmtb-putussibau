import { useEffect, useState, type FormEvent } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { Text } from '@/components/text';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { index as servicesIndex, store, update } from '@/routes/services';
import { TabFaq } from './tab-faq';
import { TabInformasiUmum } from './tab-informasi-umum';
import { TabDokumenTerkait } from './tab-dokumen-terkait';
import { TabJadwalBiaya } from './tab-jadwal-biaya';
import { TabKontakPendaftaran } from './tab-kontak-pendaftaran';
import { TabPersyaratan } from './tab-persyaratan';
import { TabProsedur } from './tab-prosedur';
import type {
    ServiceCategoryOption,
    ServiceDetail,
    ServiceDocumentOption,
    ServiceFaqRow,
    ServiceFormData,
    ServiceFormMeta,
    ServiceRequirementRow,
    ServiceStepRow,
} from '../types';

type TabKey =
    | 'informasi'
    | 'persyaratan'
    | 'prosedur'
    | 'jadwal'
    | 'kontak'
    | 'faq'
    | 'dokumen';

const TAB_KEYS: TabKey[] = [
    'informasi',
    'persyaratan',
    'prosedur',
    'jadwal',
    'kontak',
    'faq',
    'dokumen',
];

const SCALAR_TABS: TabKey[] = ['informasi', 'jadwal', 'kontak', 'dokumen'];

function isTabKey(value: string): value is TabKey {
    return (TAB_KEYS as string[]).includes(value);
}

type Props = {
    mode: 'create' | 'edit';
    categories: ServiceCategoryOption[];
    service?: ServiceDetail;
    requirements?: ServiceRequirementRow[];
    steps?: ServiceStepRow[];
    faqs?: ServiceFaqRow[];
    documents?: ServiceDocumentOption[];
} & ServiceFormMeta;

function emptyData(): ServiceFormData {
    return {
        title: '',
        slug: '',
        service_category_id: '',
        thumbnail_media_id: null,
        thumbnail_alt: '',
        summary: '',
        introduction: '',
        schedule_info: '',
        fee_info: '',
        contact_name: '',
        contact_phone: '',
        form_url: '',
        form_button_label: 'Daftar Sekarang',
        is_form_open: false,
        form_closed_message: '',
        is_emergency: false,
        is_active: false,
        sort_order: '0',
        document_ids: [],
    };
}

function toFormData(service: ServiceDetail): ServiceFormData {
    return {
        title: service.title,
        slug: service.slug,
        service_category_id: String(service.service_category_id),
        thumbnail_media_id: service.thumbnail_media_id,
        thumbnail_alt: service.thumbnail_alt ?? '',
        summary: service.summary ?? '',
        introduction: service.introduction ?? '',
        schedule_info: service.schedule_info ?? '',
        fee_info: service.fee_info ?? '',
        contact_name: service.contact_name ?? '',
        contact_phone: service.contact_phone ?? '',
        form_url: service.form_url ?? '',
        form_button_label: service.form_button_label,
        is_form_open: service.is_form_open,
        form_closed_message: service.form_closed_message ?? '',
        is_emergency: service.is_emergency,
        is_active: service.is_active,
        sort_order: String(service.sort_order),
        document_ids: service.document_ids ?? [],
    };
}

export function ServiceForm({
    mode,
    categories,
    service,
    requirements = [],
    steps = [],
    faqs = [],
    documents = [],
    labels,
    hints,
    copy,
    empty,
}: Props) {
    const page = usePage();
    const tabFromUrl = (() => {
        const value = new URL(page.url, 'http://localhost').searchParams.get(
            'tab',
        );

        return value && isTabKey(value) ? value : 'informasi';
    })();

    const [tab, setTab] = useState<TabKey>(
        mode === 'create' ? 'informasi' : tabFromUrl,
    );

    useEffect(() => {
        if (mode === 'edit') {
            setTab(tabFromUrl);
        }
    }, [mode, tabFromUrl]);

    const form = useForm<ServiceFormData>(
        service ? toFormData(service) : emptyData(),
    );

    const categoryOptions = categories.map((category) => ({
        value: String(category.id),
        label: category.name,
    }));

    const submit = () => {
        if (mode === 'create') {
            form.submit(store());

            return;
        }

        if (service) {
            form.submit(update(service.id), { preserveScroll: true });
        }
    };

    const isScalarTab = SCALAR_TABS.includes(tab);
    const isCreate = mode === 'create';

    const header = (
        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-border pb-5">
            <div className="space-y-1">
                <Text as="h3" variant="h3">
                    {isCreate ? copy.create_title : copy.edit_title}
                </Text>
                <Text as="p" variant="body-sm" color="muted">
                    {isCreate ? copy.create_description : copy.edit_description}
                </Text>
            </div>

            <div className="flex items-center gap-3">
                <Button type="button" variant="outline" asChild>
                    <Link href={servicesIndex()}>{copy.back}</Link>
                </Button>

                {isScalarTab ? (
                    <Button
                        type="button"
                        disabled={form.processing}
                        onClick={() => submit()}
                    >
                        {isCreate ? copy.save : copy.save_changes}
                    </Button>
                ) : null}
            </div>
        </div>
    );

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        submit();
    };

    return (
        <div className="space-y-6 p-6">
            <div className="space-y-6">
                {header}

                <Tabs
                    value={tab}
                    onValueChange={(value) => {
                        if (isTabKey(value)) {
                            setTab(value);
                        }
                    }}
                    className="space-y-6"
                >
                    <TabsList
                        className="flex h-auto flex-wrap justify-start gap-1 bg-transparent p-0"
                        variant="line"
                    >
                        <TabTrigger value="informasi">
                            {copy.tab_general}
                        </TabTrigger>

                        {!isCreate ? (
                            <>
                                <TabTrigger value="persyaratan">
                                    {copy.tab_requirements}
                                </TabTrigger>
                                <TabTrigger value="prosedur">
                                    {copy.tab_steps}
                                </TabTrigger>
                                <TabTrigger value="jadwal">
                                    {copy.tab_schedule}
                                </TabTrigger>
                                <TabTrigger value="kontak">
                                    {copy.tab_contact}
                                </TabTrigger>
                                <TabTrigger value="faq">
                                    {copy.tab_faq}
                                </TabTrigger>
                                <TabTrigger value="dokumen">
                                    Dokumen Terkait
                                </TabTrigger>
                            </>
                        ) : null}
                    </TabsList>

                    {isCreate ? (
                        <Alert>
                            <Info aria-hidden="true" />
                            <AlertDescription>
                                {copy.create_tabs_note}
                            </AlertDescription>
                        </Alert>
                    ) : null}

                    <TabsContent value="informasi">
                        <TabInformasiUmum
                            form={form}
                            labels={labels}
                            hints={hints}
                            copy={copy}
                            categoryOptions={categoryOptions}
                            currentThumbnailUrl={service?.thumbnail_url ?? null}
                            onSubmit={onSubmit}
                        />
                    </TabsContent>

                    {!isCreate ? (
                        <>
                            <TabsContent value="jadwal">
                                <TabJadwalBiaya
                                    form={form}
                                    labels={labels}
                                    hints={hints}
                                    onSubmit={onSubmit}
                                />
                            </TabsContent>

                            <TabsContent value="persyaratan">
                                <TabPersyaratan
                                    serviceId={service?.id ?? 0}
                                    items={requirements}
                                    labels={labels}
                                    hints={hints}
                                    copy={copy}
                                    empty={empty}
                                />
                            </TabsContent>

                            <TabsContent value="prosedur">
                                <TabProsedur
                                    serviceId={service?.id ?? 0}
                                    items={steps}
                                    labels={labels}
                                    copy={copy}
                                    empty={empty}
                                />
                            </TabsContent>

                            <TabsContent value="faq">
                                <TabFaq
                                    serviceId={service?.id ?? 0}
                                    items={faqs}
                                    labels={labels}
                                    copy={copy}
                                    empty={empty}
                                />
                            </TabsContent>

                            <TabsContent value="kontak">
                                <TabKontakPendaftaran
                                    form={form}
                                    labels={labels}
                                    hints={hints}
                                    copy={copy}
                                    onSubmit={onSubmit}
                                />
                            </TabsContent>

                            <TabsContent value="dokumen">
                                <TabDokumenTerkait
                                    form={form}
                                    documents={documents}
                                    copy={copy}
                                    onSubmit={onSubmit}
                                />
                            </TabsContent>
                        </>
                    ) : null}
                </Tabs>
            </div>
        </div>
    );
}

function TabTrigger({
    value,
    disabled,
    children,
}: {
    value: TabKey;
    disabled?: boolean;
    children: React.ReactNode;
}) {
    return (
        <TabsTrigger
            value={value}
            disabled={disabled}
            className="rounded-md border-none px-4 py-2 text-sm data-[state=active]:border-border data-[state=active]:bg-accent data-[state=active]:text-primary-on-surface data-[state=active]:shadow-none"
        >
            {children}
        </TabsTrigger>
    );
}
