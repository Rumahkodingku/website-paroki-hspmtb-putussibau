import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import SettingsController from '@/actions/App/Http/Controllers/Settings/SettingsController';
import type { SettingsProps } from '@/features/admin/pengaturan/types';
import { SettingField } from '../components/setting-field';
import { useSettingsForm } from '../hooks/use-settings-form';
import { ListHeader } from '@/components/list-parts';
import { dashboard } from '@/routes';

export default function Settings({ groups, meta }: SettingsProps) {
    const { groupsInOrder, form } = useSettingsForm(groups);
    const { data, setData, submit, processing, errors } = form;

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        submit(SettingsController.update());
    };

    return (
        <>
            <Head title="Pengaturan Situs" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Pengaturan Situs"
                    description="Identitas paroki, kontak, media sosial, SEO dasar, beranda, dan kebijakan privasi"
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Tabs defaultValue={groupsInOrder[0]} className="gap-6">
                        <TabsList
                            variant="line"
                            className="flex h-auto flex-wrap justify-start gap-2 border-b border-border bg-transparent p-0"
                        >
                            {groupsInOrder.map((group) => (
                                <TabsTrigger
                                    key={group}
                                    value={group}
                                    className="w-auto flex-none justify-start px-4 py-2 font-semibold data-[state=active]:text-primary-on-surface"
                                >
                                    {meta.groupLabels[group] ?? group}
                                </TabsTrigger>
                            ))}
                        </TabsList>

                        {groupsInOrder.map((group) => (
                            <TabsContent
                                key={group}
                                value={group}
                                className="grid gap-6"
                            >
                                {Object.entries(groups[group]).map(([key]) => (
                                    <SettingField
                                        key={key}
                                        settingKey={key}
                                        meta={meta}
                                        value={groups[group][key]}
                                        data={data.settings}
                                        errors={errors}
                                        onChange={(next) =>
                                            setData('settings', {
                                                ...data.settings,
                                                [key]: next,
                                            })
                                        }
                                    />
                                ))}
                            </TabsContent>
                        ))}
                    </Tabs>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Simpan pengaturan
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Settings.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Pengaturan Situs' },
    ],
};
