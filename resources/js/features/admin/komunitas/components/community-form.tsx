import { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { FormActions } from '@/components/form-fields';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { CategoryOption, CommunityFormData, StatusOption } from '../types';
import { TabBergabungKontak } from './tab-bergabung-kontak';
import type { CommunityFormInstance } from './community-form-instance';
import { TabInformasiDasar } from './tab-informasi-dasar';
import { TabJadwalLokasi } from './tab-jadwal-lokasi';
import { TabLogoThumbnail } from './tab-logo-thumbnail';
import { TabMediaSosial } from './tab-media-sosial';
import { TabProfilKomunitas } from './tab-profil-komunitas';
import { TabPublikasi } from './tab-publikasi';

export function emptyCommunityFormData(): CommunityFormData {
    return {
        name: '',
        slug: '',
        community_category_id: '',
        logo_media_id: null,
        logo_alt: '',
        thumbnail_media_id: null,
        thumbnail_alt: '',
        description: '',
        vision: '',
        mission: '',
        meeting_schedule: '',
        meeting_location: '',
        eligibility: '',
        joining_info: '',
        contact_name: '',
        contact_phone: '',
        show_contact: false,
        facebook: '',
        instagram: '',
        tiktok: '',
        youtube: '',
        website: '',
        status: 'forming',
        is_published: false,
    };
}

type Props = {
    initial: CommunityFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, unknown>;
    statuses: StatusOption[];
    categories: CategoryOption[];
    logoUrl?: string | null;
    thumbnailUrl?: string | null;
    submitLabel: string;
    onSubmit: (form: CommunityFormInstance) => void;
    onCancel: () => void;
};

type TabKey =
    | 'basic'
    | 'images'
    | 'profile'
    | 'schedule'
    | 'joining'
    | 'social'
    | 'publication';

const TAB_FIELDS: Record<TabKey, (keyof CommunityFormData)[]> = {
    basic: ['name', 'slug', 'community_category_id', 'status', 'description'],
    images: [
        'logo_media_id',
        'logo_alt',
        'thumbnail_media_id',
        'thumbnail_alt',
    ],
    profile: ['vision', 'mission'],
    schedule: ['meeting_schedule', 'meeting_location'],
    joining: [
        'eligibility',
        'joining_info',
        'contact_name',
        'contact_phone',
        'show_contact',
    ],
    social: ['facebook', 'instagram', 'tiktok', 'youtube', 'website'],
    publication: ['is_published'],
};

const TAB_ORDER = Object.keys(TAB_FIELDS) as TabKey[];

export function CommunityForm({
    initial,
    labels,
    hints,
    copy,
    statuses,
    categories,
    logoUrl = null,
    thumbnailUrl = null,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<CommunityFormData>(initial);
    const [tab, setTab] = useState<TabKey>('basic');

    const sections = (copy.sections ?? {}) as Record<string, string>;

    const statusLabel =
        statuses.find((option) => option.value === form.data.status)?.label ??
        form.data.status;

    const errorCount = (key: TabKey): number =>
        TAB_FIELDS[key].filter((field) => form.errors[field]).length;

    const lastSignature = useRef('');
    const signature = Object.keys(form.errors).sort().join('|');

    useEffect(() => {
        const wasClean = lastSignature.current === '';
        const hasErrors = signature !== '';

        lastSignature.current = signature;

        if (!wasClean || !hasErrors) {
            return;
        }

        const target = TAB_ORDER.find((key) => errorCount(key) > 0);

        if (target && target !== tab) {
            setTab(target);
        }
    }, [signature]);

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <Tabs
                value={tab}
                onValueChange={(value) => setTab(value as TabKey)}
                className="space-y-6"
            >
                <TabsList
                    variant="line"
                    className="flex h-auto flex-wrap justify-start gap-1 border-b border-border bg-transparent p-0"
                >
                    {TAB_ORDER.map((key) => {
                        const count = errorCount(key);

                        return (
                            <TabsTrigger
                                key={key}
                                value={key}
                                className="w-auto flex-none justify-start px-4 py-2 data-[state=active]:text-primary-on-surface"
                            >
                                {sections[key] ?? key}

                                {count > 0 ? (
                                    <Badge
                                        variant="destructive"
                                        className="px-1.5"
                                    >
                                        {count}
                                        <span className="sr-only">
                                            kesalahan
                                        </span>
                                    </Badge>
                                ) : null}
                            </TabsTrigger>
                        );
                    })}
                </TabsList>

                <TabsContent value="basic">
                    <TabInformasiDasar
                        form={form}
                        labels={labels}
                        hints={hints}
                        statuses={statuses}
                        categories={categories}
                    />
                </TabsContent>

                <TabsContent value="images">
                    <TabLogoThumbnail
                        form={form}
                        labels={labels}
                        hints={hints}
                        logoUrl={logoUrl}
                        thumbnailUrl={thumbnailUrl}
                    />
                </TabsContent>

                <TabsContent value="profile">
                    <TabProfilKomunitas form={form} labels={labels} />
                </TabsContent>

                <TabsContent value="schedule">
                    <TabJadwalLokasi form={form} labels={labels} />
                </TabsContent>

                <TabsContent value="joining">
                    <TabBergabungKontak
                        form={form}
                        labels={labels}
                        hints={hints}
                    />
                </TabsContent>

                <TabsContent value="social">
                    <TabMediaSosial form={form} labels={labels} hints={hints} />
                </TabsContent>

                <TabsContent value="publication">
                    <TabPublikasi
                        form={form}
                        labels={labels}
                        sections={sections}
                        statusLabel={statusLabel}
                    />
                </TabsContent>
            </Tabs>

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
