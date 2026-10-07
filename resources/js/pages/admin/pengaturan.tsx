import { Head, Link, useForm } from '@inertiajs/react';
import { Suspense, lazy } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import SettingsController from '@/actions/App/Http/Controllers/Settings/SettingsController';
import { edit } from '@/routes/settings';
import type { SettingValue, SettingsMeta } from '@/types';

/*
 * Tiptap and ProseMirror together are a few hundred kilobytes, and this page is
 * mostly text inputs. Loaded eagerly they would land in the settings chunk and be
 * downloaded on every visit to a form where only one tab uses them.
 *
 * Radix unmounts an inactive tab by default, so making the import lazy is enough:
 * the editor chunk is fetched when the tab that contains it is first opened, and
 * not before. That also keeps it out of the way of the two tabs an administrator
 * actually visits.
 */
const RichTextEditor = lazy(() =>
    import('@/components/rich-text-editor').then((module) => ({
        default: module.RichTextEditor,
    })),
);

/** Keeps the tab panel from collapsing while the editor chunk is in flight. */
function EditorFallback() {
    return (
        <div
            aria-busy="true"
            aria-label="Memuat penyunting teks"
            className="min-h-40 rounded-md border border-input bg-muted/30"
        />
    );
}

/*
 * Site settings form, PRD 5.2 "Pengaturan situs dan kontak".
 *
 * The payload is built explicitly instead of relying on native form
 * serialisation, and that is not a style preference. A Radix Switch renders a
 * hidden checkbox that only submits when it is on, so a switch left off would
 * drop its key from the request entirely and the stored setting would keep its
 * previous value while the form appears to show something else. Posting the
 * whole key set every time makes the form's state and the stored state the same
 * thing.
 *
 * @see docs/DECISIONS.md D-23
 */

type Props = {
    groups: Record<string, Record<string, SettingValue>>;
    meta: SettingsMeta;
};

const GROUP_ORDER = [
    'identitas',
    'kontak',
    'sosial',
    'seo',
    'beranda',
    'privasi',
] as const;

type FieldKind =
    | 'text'
    | 'multiline'
    | 'richtext'
    | 'url'
    | 'number'
    | 'switch';

function fieldKind(
    key: string,
    meta: SettingsMeta,
    value: SettingValue,
): FieldKind {
    if (typeof value === 'boolean') {
        return 'switch';
    }
    // Checked before `multiline` because a rich text field is also long, and a
    // textarea for it would silently discard every tag on save.
    if (meta.richtext.includes(key)) {
        return 'richtext';
    }
    if (meta.multiline.includes(key)) {
        return 'multiline';
    }
    if (meta.numericRanges[key]) {
        return 'number';
    }
    if (meta.urls.includes(key)) {
        return 'url';
    }
    return 'text';
}

/** Everything the backend accepts is a string on the wire. */
function toWire(value: SettingValue): string {
    if (value === null || value === undefined) {
        return '';
    }
    if (typeof value === 'boolean') {
        return value ? '1' : '0';
    }

    return String(value);
}

export default function Settings({ groups, meta }: Props) {
    const groupsInOrder = GROUP_ORDER.filter((group) => groups[group]);

    const initial = Object.fromEntries(
        groupsInOrder
            .flatMap((group) => Object.entries(groups[group]))
            .map(([key, value]) => [key, toWire(value)]),
    ) as Record<string, string>;

    const { data, setData, post, processing, errors, reset } = useForm<{
        settings: Record<string, string>;
    }>({ settings: initial });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(SettingsController.update().url);
    };

    return (
        <>
            <Head title="Pengaturan Situs" />

            <h1 className="sr-only">Pengaturan Situs</h1>

            <div className="space-y-6 p-6">
                <Heading
                    variant="small"
                    title="Pengaturan Situs"
                    description="Identitas paroki, kontak, media sosial, SEO dasar, beranda, dan kebijakan privasi"
                />

                <form onSubmit={submit} className="space-y-6">
                    <Tabs defaultValue={groupsInOrder[0]} className="gap-6">
                        <TabsList className="flex-wrap">
                            {groupsInOrder.map((group) => (
                                <TabsTrigger key={group} value={group}>
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
                                {Object.entries(groups[group]).map(
                                    ([key, value]) => {
                                        const kind = fieldKind(
                                            key,
                                            meta,
                                            value,
                                        );
                                        const label = meta.labels[key] ?? key;
                                        const range = meta.numericRanges[key];
                                        const inputId = `setting-${key}`;
                                        const message =
                                            errors[`settings.${key}`];

                                        const set = (next: string) =>
                                            setData('settings', {
                                                ...data.settings,
                                                [key]: next,
                                            });

                                        return (
                                            <div
                                                key={key}
                                                className="grid gap-2"
                                            >
                                                {kind === 'switch' ? (
                                                    <div className="flex items-center justify-between gap-4">
                                                        <Label
                                                            htmlFor={inputId}
                                                        >
                                                            {label}
                                                        </Label>
                                                        <Switch
                                                            id={inputId}
                                                            checked={
                                                                data.settings[
                                                                    key
                                                                ] === '1'
                                                            }
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                set(
                                                                    checked
                                                                        ? '1'
                                                                        : '0',
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                ) : (
                                                    <>
                                                        <Label
                                                            htmlFor={inputId}
                                                        >
                                                            {label}
                                                        </Label>

                                                        {kind === 'richtext' ? (
                                                            <Suspense
                                                                fallback={
                                                                    <EditorFallback />
                                                                }
                                                            >
                                                                <RichTextEditor
                                                                    id={inputId}
                                                                    value={
                                                                        data
                                                                            .settings[
                                                                            key
                                                                        ] ?? ''
                                                                    }
                                                                    onChange={(
                                                                        next,
                                                                    ) =>
                                                                        set(
                                                                            next,
                                                                        )
                                                                    }
                                                                />
                                                            </Suspense>
                                                        ) : kind ===
                                                          'multiline' ? (
                                                            <Textarea
                                                                id={inputId}
                                                                value={
                                                                    data
                                                                        .settings[
                                                                        key
                                                                    ] ?? ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    set(
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                            />
                                                        ) : (
                                                            <Input
                                                                id={inputId}
                                                                type={
                                                                    kind ===
                                                                    'number'
                                                                        ? 'number'
                                                                        : kind ===
                                                                            'url'
                                                                          ? 'url'
                                                                          : 'text'
                                                                }
                                                                min={range?.[0]}
                                                                max={range?.[1]}
                                                                value={
                                                                    data
                                                                        .settings[
                                                                        key
                                                                    ] ?? ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    set(
                                                                        event
                                                                            .target
                                                                            .value,
                                                                    )
                                                                }
                                                            />
                                                        )}
                                                    </>
                                                )}

                                                {meta.hints[key] ? (
                                                    <p className="text-caption text-muted-foreground">
                                                        {meta.hints[key]}
                                                    </p>
                                                ) : null}

                                                <InputError message={message} />
                                            </div>
                                        );
                                    },
                                )}
                            </TabsContent>
                        ))}
                    </Tabs>

                    <Separator />

                    <div className="flex items-center gap-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="save-settings-button"
                        >
                            Simpan Pengaturan
                        </Button>

                        <Button asChild type="button" variant="outline">
                            <Link href={edit()}>Batal</Link>
                        </Button>

                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => reset()}
                            disabled={processing}
                        >
                            Kembalikan
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Settings.layout = {
    breadcrumbs: [{ title: 'Pengaturan Situs', href: edit() }],
};
