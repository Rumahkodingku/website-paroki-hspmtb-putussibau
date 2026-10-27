import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

const EMPTY_VALUE = '__empty__';

function toSelectValue(value: string): string {
    return value === '' ? EMPTY_VALUE : value;
}

function fromSelectValue(value: string): string {
    return value === EMPTY_VALUE ? '' : value;
}

export function FormLabel({
    htmlFor,
    isRequired = false,
    hint,
    error,
    disabled = false,
    children,
}: {
    htmlFor: string;
    isRequired?: boolean;
    hint?: string;
    error?: string;
    disabled?: boolean;
    children: ReactNode;
}) {
    return (
        <div className="space-y-1">
            <Label
                htmlFor={htmlFor}
                className={`${disabled ? 'opacity-50' : undefined} font-semibold`}
            >
                {children}{' '}
                {isRequired ? <span className="text-red-600">*</span> : null}
            </Label>

            {hint ? (
                <Text as="p" variant="body-sm" color="muted">
                    {hint}
                </Text>
            ) : null}

            <InputError message={error} />
        </div>
    );
}

export function Field({
    id,
    label,
    hint,
    error,
    children,
    required,
    isRequired,
}: {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    children: ReactNode;
    required?: boolean;
    isRequired?: boolean;
}) {
    return (
        <div className="space-y-2">
            <FormLabel
                htmlFor={id}
                isRequired={isRequired ?? required}
                hint={hint}
                error={error}
            >
                {label}
            </FormLabel>
            <div className="mt-2"> {children}</div>
        </div>
    );
}
export function ChoiceSelect({
    id,
    value,
    onChange,
    options,
    placeholder,
    disabled,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: Array<{ value: string; label: string }>;
    placeholder?: string;
    disabled?: boolean;
}) {
    return (
        <Select
            value={toSelectValue(value)}
            onValueChange={(next) => onChange(fromSelectValue(next))}
            disabled={disabled}
        >
            <SelectTrigger
                id={id}
                className="h-11 w-full border-border bg-background py-5 font-normal shadow-none focus-visible:border-primary"
            >
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>

            <SelectContent>
                {placeholder ? (
                    <SelectItem value={EMPTY_VALUE}>{placeholder}</SelectItem>
                ) : null}

                {options.map((option) => (
                    <SelectItem
                        className="cursor-pointer p-2.5"
                        key={option.value}
                        value={toSelectValue(option.value)}
                    >
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export function ActiveSwitch({
    id,
    checked,
    onChange,
    activeLabel,
}: {
    id: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    activeLabel: string;
}) {
    return (
        <div className="flex items-center justify-between gap-4 border-t pt-6">
            <div className="space-y-1">
                <Label htmlFor={id}>{activeLabel}</Label>
            </div>
            <Switch id={id} checked={checked} onCheckedChange={onChange} />
        </div>
    );
}

export function FormTextarea({
    id,
    value,
    onChange,
    rows = 3,
    placeholder = '',
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    rows?: number;
    placeholder?: string;
}) {
    return (
        <Textarea
            id={id}
            rows={rows}
            value={value}
            onChange={(event: any) => onChange(event.target.value)}
            className="h-24 w-full resize-none border-border shadow-none focus-visible:border-primary"
            placeholder={placeholder}
        />
    );
}

export function FormInput({
    id,
    value,
    onChange,
    type = 'text',
    inputMode,
    required,
    min,
    max,
    placeholder,
    list,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    type?: 'text' | 'time' | 'date' | 'datetime-local' | 'number' | 'url';
    inputMode?: 'text' | 'numeric' | 'decimal' | 'url';
    required?: boolean;
    min?: string | number;
    max?: string | number;
    placeholder?: string;
    list?: string;
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
            list={list}
            onChange={(event: any) => onChange(event.target.value)}
            className="border-border focus-visible:border-primary"
            placeholder={placeholder}
        />
    );
}

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
        <div className="flex flex-wrap items-center justify-end gap-3">
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
