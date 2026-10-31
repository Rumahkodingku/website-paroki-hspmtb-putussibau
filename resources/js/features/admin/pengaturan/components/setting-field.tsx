import { Suspense, lazy } from 'react';
import InputError from '@/components/input-error';
import { MediaUploadField } from '@/components/media-upload-field';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FormLabel } from '@/components/form-fields';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { OfficeHoursEditor } from './office-hours-editor';
import { fieldKind, type SettingsFormValue } from '../hooks/use-settings-form';
import type { SettingsMeta, SettingValue } from '../types';

const RichTextEditor = lazy(() =>
    import('@/components/rich-text-editor').then((module) => ({
        default: module.RichTextEditor,
    })),
);

function EditorFallback() {
    return (
        <div
            aria-busy="true"
            aria-label="Memuat penyunting teks"
            className="min-h-40 rounded-md border border-input bg-muted/30"
        />
    );
}

type Props = {
    settingKey: string;
    meta: SettingsMeta;
    value: SettingValue;
    data: Record<string, SettingsFormValue>;
    errors: Record<string, string | undefined>;
    onChange: (next: SettingsFormValue) => void;
};

export function SettingField({ settingKey, meta, value, data, errors, onChange }: Props) {
    const kind = fieldKind(settingKey, meta, value);
    const label = meta.labels[settingKey] ?? settingKey;
    const inputId = `setting-${settingKey}`;
    const message = errors[`settings.${settingKey}`];
    const raw = data[settingKey];
    const stringValue = typeof raw === 'string' ? raw : '';
    const mediaCurrentUrl = meta.mediaUrls[settingKey] ?? null;
    const onMediaChange = (mediaId: number | null) =>
        onChange(mediaId === null ? '' : String(mediaId));

    if (kind === 'switch') {
        return (
            <div className="flex items-center justify-between gap-4">
                <FormLabel htmlFor={inputId}>{label}</FormLabel>
                <Switch
                    id={inputId}
                    checked={data[settingKey] === '1'}
                    onCheckedChange={(checked) =>
                        onChange(checked ? '1' : '0')
                    }
                />
            </div>
        );
    }

    if (kind === 'office-hours') {
        return (
            <>
                <Text as="p" variant="heading-sm" color="foreground">
                    {label}
                </Text>
                <OfficeHoursEditor
                    id={inputId}
                    value={data[settingKey] ?? null}
                    errors={errors}
                    onChange={onChange}
                />
            </>
        );
    }

    if (kind === 'media') {
        if (settingKey === 'logo') {
            return (
                <MediaUploadField
                    id={inputId}
                    variant="logo"
                    label={label}
                    hint={meta.hints[settingKey]}
                    currentUrl={mediaCurrentUrl}
                    fieldName={settingKey}
                    removable
                    onMediaChange={onMediaChange}
                />
            );
        }
        if (settingKey === 'favicon') {
            return (
                <MediaUploadField
                    id={inputId}
                    variant="image"
                    aspect={1}
                    label={label}
                    hint={meta.hints[settingKey]}
                    emptyHint="Belum ada favicon."
                    currentUrl={mediaCurrentUrl}
                    fieldName={settingKey}
                    removable
                    onMediaChange={onMediaChange}
                />
            );
        }
        if (settingKey === 'home_hero_image') {
            return (
                <MediaUploadField
                    id={inputId}
                    variant="image"
                    aspect={16 / 9}
                    label={label}
                    hint={meta.hints[settingKey]}
                    emptyHint="Belum ada gambar hero."
                    currentUrl={mediaCurrentUrl}
                    fieldName={settingKey}
                    removable
                    onMediaChange={onMediaChange}
                />
            );
        }
        return (
            <MediaUploadField
                id={inputId}
                variant="image"
                label={label}
                hint={meta.hints[settingKey]}
                currentUrl={mediaCurrentUrl}
                fieldName={settingKey}
                removable
                onMediaChange={onMediaChange}
            />
        );
    }

    if (kind === 'richtext') {
        return (
            <div className="grid gap-2">
                <FormLabel htmlFor={inputId} isRequired={false}>
                    {label}
                </FormLabel>
                <Suspense fallback={<EditorFallback />}>
                    <RichTextEditor
                        id={inputId}
                        value={stringValue}
                        onChange={(value) => onChange(value)}
                    />
                </Suspense>
                <InputError message={message} />
            </div>
        );
    }

    if (kind === 'multiline') {
        return (
            <div className="grid gap-2">
                <FormLabel htmlFor={inputId}>{label}</FormLabel>
                <Textarea
                    id={inputId}
                    value={stringValue}
                    onChange={(event: React.ChangeEvent<HTMLTextAreaElement>) =>
                        onChange(event.target.value)
                    }
                    maxLength={meta.numericRanges[settingKey] ? undefined : 2048}
                    className="min-h-24 resize-y border-border shadow-none focus-visible:border-primary"
                />
                <InputError message={message} />
            </div>
        );
    }

    if (kind === 'number') {
        const range = meta.numericRanges[settingKey];
        return (
            <div className="grid gap-2">
                <FormLabel htmlFor={inputId}>{label}</FormLabel>
                <Input
                    id={inputId}
                    type="number"
                    value={stringValue}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) =>
                        onChange(event.target.value)
                    }
                    min={range?.[0]}
                    max={range?.[1]}
                    className="h-11"
                />
                <InputError message={message} />
            </div>
        );
    }

    if (kind === 'url') {
        return (
            <div className="grid gap-2">
                <FormLabel htmlFor={inputId}>{label}</FormLabel>
                <Input
                    id={inputId}
                    type="url"
                    value={stringValue}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) =>
                        onChange(event.target.value)
                    }
                    className="h-11"
                />
                <InputError message={message} />
            </div>
        );
    }

    return (
        <div className="grid gap-2">
            <FormLabel htmlFor={inputId}>{label}</FormLabel>
            <Input
                id={inputId}
                value={stringValue}
                onChange={(event: React.ChangeEvent<HTMLInputElement>) =>
                    onChange(event.target.value)
                }
                className="h-11"
            />
            <InputError message={message} />
        </div>
    );
}