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
    destroy as destroyFaq,
    reorder as reorderFaqs,
    store as storeFaq,
    update as updateFaq,
} from '@/routes/service-faqs';
import type { ServiceFaqRow } from '../types';

type Props = {
    serviceId: number;
    items: ServiceFaqRow[];
    labels: Record<string, string>;
    copy: Record<string, string>;
    empty: Record<string, string>;
};

type FormData = { question: string; answer: string };

export function TabFaq({ serviceId, items, labels, copy, empty }: Props) {
    const [editing, setEditing] = useState<ServiceFaqRow | null>(null);
    const [creating, setCreating] = useState(false);
    const [deleting, setDeleting] = useState<ServiceFaqRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;

        if (target < 0 || target >= items.length) {
            return;
        }

        const next = [...items];
        [next[index], next[target]] = [next[target], next[index]];

        router.post(
            reorderFaqs(serviceId),
            { ids: next.map((item) => item.id) },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <Text as="h3" variant="h3">
                        {copy.faq_title}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {copy.faq_description}
                    </Text>
                </div>

                <Button type="button" onClick={() => setCreating(true)}>
                    {copy.add_faq}
                </Button>
            </div>

            {items.length === 0 ? (
                <Text
                    as="p"
                    variant="body"
                    color="muted"
                    className="rounded-lg border border-dashed border-border p-6"
                >
                    {empty.faqs}
                </Text>
            ) : (
                <ul className="space-y-3">
                    {items.map((item, index) => (
                        <li
                            key={item.id}
                            className="rounded-lg border border-border p-4"
                        >
                            <div className="flex items-start gap-4">
                                <div className="min-w-0 flex-1 space-y-1">
                                    <Text as="p" variant="heading-sm">
                                        {item.question}
                                    </Text>
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {item.answer}
                                    </Text>
                                </div>

                                <div className="flex shrink-0 items-center gap-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`${copy.move_up}: ${item.question}`}
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
                                        aria-label={`${copy.move_down}: ${item.question}`}
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
                                        aria-label={`${copy.edit_action}: ${item.question}`}
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
                                        aria-label={`${copy.delete}: ${item.question}`}
                                        onClick={() => setDeleting(item)}
                                    >
                                        <Trash
                                            className="size-4 text-primary-on-surface"
                                            aria-hidden="true"
                                        />
                                    </Button>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {creating || editing ? (
                <FaqDialog
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
                    title={copy.delete_faq}
                    confirmLabel={copy.delete}
                    cancelLabel={copy.cancel}
                    disabled={submitting}
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    description={`${deleting.question} — ${copy.confirm_delete_notice}`}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyFaq([serviceId, deleting.id]), {
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

function FaqDialog({
    serviceId,
    item,
    labels,
    copy,
    onClose,
}: {
    serviceId: number;
    item: ServiceFaqRow | null;
    labels: Record<string, string>;
    copy: Record<string, string>;
    onClose: () => void;
}) {
    const form = useForm<FormData>({
        question: item?.question ?? '',
        answer: item?.answer ?? '',
    });

    const submit = () => {
        const options = { preserveScroll: true, onSuccess: () => onClose() };

        if (item) {
            form.submit(updateFaq([serviceId, item.id]), options);
        } else {
            form.submit(storeFaq(serviceId), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => (open ? null : onClose())}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {item ? copy.edit_faq : copy.add_faq}
                    </DialogTitle>
                    <DialogDescription>
                        {copy.faq_dialog_description}
                    </DialogDescription>
                </DialogHeader>

                <form
                    noValidate
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit();
                    }}
                    className="space-y-4"
                >
                    <Field
                        id="faq-question"
                        label={labels.faq_question}
                        error={form.errors.question}
                        required
                    >
                        <FormInput
                            id="faq-question"
                            value={form.data.question}
                            onChange={(value) =>
                                form.setData('question', value)
                            }
                            required
                        />
                    </Field>

                    <Field
                        id="faq-answer"
                        label={labels.faq_answer}
                        error={form.errors.answer}
                        required
                    >
                        <FormTextarea
                            id="faq-answer"
                            value={form.data.answer}
                            onChange={(value) => form.setData('answer', value)}
                            rows={3}
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
