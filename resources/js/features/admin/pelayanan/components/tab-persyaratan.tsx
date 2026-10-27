import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Trash } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import {
    ChoiceSelect,
    Field,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    destroy as destroyRequirement,
    reorder as reorderRequirements,
    store as storeRequirement,
    update as updateRequirement,
} from '@/routes/service-requirements';
import type { ServiceRequirementRow } from '../types';

type Props = {
    serviceId: number;
    items: ServiceRequirementRow[];
    labels: Record<string, string>;
    hints: Record<string, string>;
    conditions: Record<string, string>;
    copy: Record<string, string>;
    empty: Record<string, string>;
};

type FormData = {
    text: string;
    note: string;
    requirement_condition: string;
    applies_to: string;
};

export function TabPersyaratan({
    serviceId,
    items,
    labels,
    hints,
    conditions,
    copy,
    empty,
}: Props) {
    const [editing, setEditing] = useState<ServiceRequirementRow | null>(null);
    const [creating, setCreating] = useState(false);
    const [deleting, setDeleting] = useState<ServiceRequirementRow | null>(
        null,
    );
    const [submitting, setSubmitting] = useState(false);

    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;

        if (target < 0 || target >= items.length) {
            return;
        }

        const next = [...items];
        [next[index], next[target]] = [next[target], next[index]];

        router.post(
            reorderRequirements(serviceId),
            { ids: next.map((item) => item.id) },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <Text as="h3" variant="h3">
                        {copy.requirement_title}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {copy.requirement_description}
                    </Text>
                </div>

                <Button type="button" onClick={() => setCreating(true)}>
                    {copy.add_requirement}
                </Button>
            </div>

            {items.length === 0 ? (
                <Text
                    as="p"
                    variant="body"
                    color="muted"
                    className="rounded-lg border border-dashed border-border p-6"
                >
                    {empty.requirements}
                </Text>
            ) : (
                <ul className="space-y-3">
                    {items.map((item, index) => (
                        <li
                            key={item.id}
                            className="flex items-start gap-4 rounded-lg border border-border p-4"
                        >
                            <span
                                aria-hidden="true"
                                className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-medium text-secondary-foreground"
                            >
                                {index + 1}
                            </span>

                            <div className="min-w-0 flex-1 space-y-1">
                                <Text as="p" variant="heading-sm">
                                    {item.text}
                                </Text>
                                {item.applies_to ? (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        Untuk {item.applies_to}
                                    </Text>
                                ) : null}
                                {item.requirement_condition !== 'required' ? (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {conditions[item.requirement_condition]}
                                    </Text>
                                ) : null}
                                {item.note ? (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {item.note}
                                    </Text>
                                ) : null}
                            </div>

                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`${copy.move_up}: ${item.text}`}
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                >
                                    <ArrowUp
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`${copy.move_down}: ${item.text}`}
                                    disabled={index === items.length - 1}
                                    onClick={() => move(index, 1)}
                                >
                                    <ArrowDown
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`${copy.edit_action}: ${item.text}`}
                                    onClick={() => setEditing(item)}
                                >
                                    <Pencil
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`${copy.delete}: ${item.text}`}
                                    onClick={() => setDeleting(item)}
                                >
                                    <Trash
                                        className="size-4 text-primary-on-surface"
                                        aria-hidden="true"
                                    />
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Text
                as="p"
                variant="body-sm"
                color="muted"
                className="rounded-lg bg-muted p-4"
            >
                {copy.order_hint}
            </Text>

            {creating || editing ? (
                <RequirementDialog
                    serviceId={serviceId}
                    item={editing}
                    labels={labels}
                    hints={hints}
                    conditions={conditions}
                    copy={copy}
                    onClose={() => {
                        setCreating(false);
                        setEditing(null);
                    }}
                />
            ) : null}

            {deleting ? (
                <ConfirmDialog
                    open
                    destructive
                    title={copy.delete_requirement}
                    confirmLabel={copy.delete}
                    cancelLabel={copy.cancel}
                    disabled={submitting}
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    description={`${deleting.text} — ${copy.confirm_delete_notice}`}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(
                            destroyRequirement([serviceId, deleting.id]),
                            {
                                preserveScroll: true,
                                onFinish: () => {
                                    setSubmitting(false);
                                    setDeleting(null);
                                },
                            },
                        );
                    }}
                />
            ) : null}
        </div>
    );
}

function RequirementDialog({
    serviceId,
    item,
    labels,
    hints,
    conditions,
    copy,
    onClose,
}: {
    serviceId: number;
    item: ServiceRequirementRow | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    conditions: Record<string, string>;
    copy: Record<string, string>;
    onClose: () => void;
}) {
    const form = useForm<FormData>({
        text: item?.text ?? '',
        note: item?.note ?? '',
        requirement_condition: item?.requirement_condition ?? 'required',
        applies_to: item?.applies_to ?? '',
    });

    const submit = () => {
        const options = {
            preserveScroll: true,
            onSuccess: () => onClose(),
        };

        if (item) {
            form.submit(updateRequirement([serviceId, item.id]), options);
        } else {
            form.submit(storeRequirement(serviceId), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => (open ? null : onClose())}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {item ? copy.edit_requirement : copy.add_requirement}
                    </DialogTitle>
                    <DialogDescription>
                        {copy.requirement_dialog_description}
                    </DialogDescription>
                </DialogHeader>

                <form
                    noValidate
                    onSubmit={(event: any) => {
                        event.preventDefault();
                        submit();
                    }}
                    className="space-y-4"
                >
                    <Field
                        id="requirement-text"
                        label={labels.requirement_text}
                        error={form.errors.text}
                        required
                    >
                        <FormInput
                            id="requirement-text"
                            value={form.data.text}
                            onChange={(value) => form.setData('text', value)}
                            placeholder={copy.placeholder_requirement}
                            required
                        />
                    </Field>

                    <Field
                        id="requirement-note"
                        label={labels.requirement_note}
                        hint={hints.requirement_note}
                        error={form.errors.note}
                    >
                        <FormTextarea
                            id="requirement-note"
                            value={form.data.note}
                            onChange={(value) => form.setData('note', value)}
                            rows={2}
                            placeholder="Contoh: Catatan persyaratan"
                        />
                    </Field>

                    <Field
                        id="requirement-condition"
                        label={labels.requirement_condition}
                        error={form.errors.requirement_condition}
                        required
                    >
                        <ChoiceSelect
                            id="requirement-condition"
                            value={form.data.requirement_condition}
                            onChange={(value) =>
                                form.setData('requirement_condition', value)
                            }
                            options={Object.entries(conditions).map(
                                ([value, label]) => ({ value, label }),
                            )}
                            placeholder={undefined}
                        />
                    </Field>

                    <Field
                        id="requirement-applies-to"
                        label={labels.applies_to}
                        hint={hints.applies_to}
                        error={form.errors.applies_to}
                    >
                        <FormInput
                            id="requirement-applies-to"
                            value={form.data.applies_to}
                            onChange={(value) =>
                                form.setData('applies_to', value)
                            }
                            list="requirement-applies-to-suggestions"
                            placeholder="Contoh: Semua umat"
                        />
                        <datalist id="requirement-applies-to-suggestions">
                            {[
                                'Orang tua',
                                'Wali baptis',
                                'Calon baptis dewasa',
                                'Calon penerima sakramen',
                                'Keluarga',
                                'Umum',
                            ].map((suggestion) => (
                                <option key={suggestion} value={suggestion} />
                            ))}
                        </datalist>
                    </Field>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            {copy.cancel}
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {item ? copy.save : copy.add}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
