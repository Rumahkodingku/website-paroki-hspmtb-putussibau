import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Trash } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field, FormInput, FormTextarea } from '@/components/form-fields';
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
    destroy as destroyStep,
    reorder as reorderSteps,
    store as storeStep,
    update as updateStep,
} from '@/routes/service-steps';
import type { ServiceStepRow } from '../types';

type Props = {
    serviceId: number;
    items: ServiceStepRow[];
    labels: Record<string, string>;
    copy: Record<string, string>;
    empty: Record<string, string>;
};

type FormData = { title: string; description: string };

export function TabProsedur({ serviceId, items, labels, copy, empty }: Props) {
    const [editing, setEditing] = useState<ServiceStepRow | null>(null);
    const [creating, setCreating] = useState(false);
    const [deleting, setDeleting] = useState<ServiceStepRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;

        if (target < 0 || target >= items.length) {
            return;
        }

        const next = [...items];
        [next[index], next[target]] = [next[target], next[index]];

        router.post(
            reorderSteps(serviceId),
            { ids: next.map((item) => item.id) },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <Text as="h3" variant="h3">
                        {copy.step_title}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {copy.step_description}
                    </Text>
                </div>

                <Button type="button" onClick={() => setCreating(true)}>
                    {copy.add_step}
                </Button>
            </div>

            {items.length === 0 ? (
                <Text
                    as="p"
                    variant="body"
                    color="muted"
                    className="rounded-lg border border-dashed border-border p-6"
                >
                    {empty.steps}
                </Text>
            ) : (
                <ol className="space-y-3">
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
                                    {item.title}
                                </Text>
                                {item.description ? (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {item.description}
                                    </Text>
                                ) : null}
                            </div>

                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`${copy.move_up}: ${item.title}`}
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
                                    aria-label={`${copy.move_down}: ${item.title}`}
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
                                    aria-label={`${copy.edit_action}: ${item.title}`}
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
                                    aria-label={`${copy.delete}: ${item.title}`}
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
                </ol>
            )}

            {creating || editing ? (
                <StepDialog
                    serviceId={serviceId}
                    item={editing}
                    labels={labels}
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
                    title={copy.delete_step}
                    confirmLabel={copy.delete}
                    cancelLabel={copy.cancel}
                    disabled={submitting}
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    description={`${deleting.title} — ${copy.confirm_delete_notice}`}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyStep([serviceId, deleting.id]), {
                            preserveScroll: true,
                            onFinish: () => {
                                setSubmitting(false);
                                setDeleting(null);
                            },
                        });
                    }}
                />
            ) : null}
        </div>
    );
}

function StepDialog({
    serviceId,
    item,
    labels,
    copy,
    onClose,
}: {
    serviceId: number;
    item: ServiceStepRow | null;
    labels: Record<string, string>;
    copy: Record<string, string>;
    onClose: () => void;
}) {
    const form = useForm<FormData>({
        title: item?.title ?? '',
        description: item?.description ?? '',
    });

    const submit = () => {
        const options = { preserveScroll: true, onSuccess: () => onClose() };

        if (item) {
            form.submit(updateStep([serviceId, item.id]), options);
        } else {
            form.submit(storeStep(serviceId), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => (open ? null : onClose())}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {item ? copy.edit_step : copy.add_step}
                    </DialogTitle>
                    <DialogDescription>
                        {copy.step_dialog_description}
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
                        id="step-title"
                        label={labels.step_title}
                        error={form.errors.title}
                        required
                    >
                        <FormInput
                            id="step-title"
                            value={form.data.title}
                            onChange={(value) => form.setData('title', value)}
                            placeholder={copy.placeholder_step}
                            required
                        />
                    </Field>

                    <Field
                        id="step-description"
                        label={labels.step_description}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="step-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={2}
                            placeholder="Contoh: Deskripsi langkah"
                        />
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
