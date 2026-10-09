import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

/*
 * The building blocks every form in this module is made of.
 *
 * Three small components rather than one big form per entity, because the four
 * forms differ by which fields they have and not by how a field is rendered. A
 * label, an error and a hint behave the same for a location's address and for a
 * notice's end date, and writing that three more times is how one of them ends up
 * without a visible focus ring.
 */

/** A label, its control, an optional hint and the field's validation error. */
export function Field({
    id,
    label,
    hint,
    error,
    children,
}: {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint ? (
                <Text as="p" variant="caption" color="muted">
                    {hint}
                </Text>
            ) : null}
            <InputError message={error} />
        </div>
    );
}

/**
 * A native select.
 *
 * The shadcn Select is a Radix popover, which does not submit with a plain form
 * and needs no-JS behaviour to be reachable. Here the options are a fixed list
 * the browser already knows how to present, and a native control keeps the form
 * working before JavaScript finishes loading — which matters on the page a
 * parish secretary opens on a phone with a slow connection (ADM-01).
 */
export function ChoiceSelect({
    id,
    value,
    onChange,
    options,
    placeholder,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: Array<{ value: string; label: string }>;
    placeholder?: string;
}) {
    return (
        <select
            id={id}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            className="h-11 w-full rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
        >
            {placeholder ? <option value="">{placeholder}</option> : null}
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

/**
 * The active switch every entity in this module shares.
 *
 * is_active is the whole lifecycle here — nothing is deleted (D-34) — so this is
 * the one control that decides whether a record is public.
 */
export function ActiveSwitch({
    id,
    checked,
    onChange,
    activeLabel,
    inactiveLabel,
}: {
    id: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    activeLabel: string;
    inactiveLabel: string;
}) {
    return (
        <div className="flex items-center justify-between gap-4 border-t pt-6">
            <div className="space-y-1">
                <Label htmlFor={id}>{activeLabel}</Label>
                <Text as="p" variant="caption" color="muted">
                    {inactiveLabel}
                </Text>
            </div>
            <Switch id={id} checked={checked} onCheckedChange={onChange} />
        </div>
    );
}

/**
 * A textarea bound to one key of a useForm, with the project's border token.
 */
export function FormTextarea({
    id,
    value,
    onChange,
    rows = 3,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    rows?: number;
}) {
    return (
        <Textarea
            id={id}
            rows={rows}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            className="border-hairline focus-visible:border-primary"
        />
    );
}

/**
 * A text input bound to one key of a useForm.
 */
export function FormInput({
    id,
    value,
    onChange,
    type = 'text',
    inputMode,
    required,
    min,
    max,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    type?: 'text' | 'time' | 'date' | 'datetime-local' | 'number' | 'url';
    inputMode?: 'text' | 'numeric' | 'decimal' | 'url';
    required?: boolean;
    min?: string | number;
    max?: string | number;
}) {
    return (
        <Input
            id={id}
            type={type}
            inputMode={inputMode}
            value={value}
            required={required}
            min={min}
            max={max}
            onChange={(event) => onChange(event.target.value)}
            className="border-hairline focus-visible:border-primary"
        />
    );
}

/**
 * The submit row.
 *
 * `form.processing` disables the button, which is roadmap 6.3's "prevent double
 * submit": a second click while the first request is in flight would create two
 * schedules, and the unique index would turn the second into a confusing error.
 */
export function FormActions({
    processing,
    submitLabel,
    onCancel,
    extra,
}: {
    processing: boolean;
    submitLabel: string;
    onCancel: () => void;
    extra?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-center gap-3">
            <Button type="submit" disabled={processing}>
                {submitLabel}
            </Button>
            <Button type="button" variant="outline" onClick={onCancel}>
                Batal
            </Button>
            {extra}
        </div>
    );
}
