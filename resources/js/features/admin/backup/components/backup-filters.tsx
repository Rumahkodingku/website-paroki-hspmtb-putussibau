import { useEffect, useState } from 'react';
import { SlidersHorizontal } from 'lucide-react';
import { DatePicker } from '@/components/date-picker';
import { Field } from '@/components/form-fields';
import {
    FilterSelect,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BackupFilters as Filters } from '../types';

const TYPE_OPTIONS = [
    { value: '', label: 'Semua tipe' },
    { value: 'database', label: 'Database' },
    { value: 'full', label: 'Lengkap' },
];

const SOURCE_OPTIONS = [
    { value: '', label: 'Semua sumber' },
    { value: 'manual', label: 'Manual' },
    { value: 'scheduled', label: 'Terjadwal' },
    { value: 'uploaded', label: 'Unggahan' },
    { value: 'pre_restore', label: 'Safety backup' },
];

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'queued', label: 'Menunggu antrean' },
    { value: 'running', label: 'Sedang dibuat' },
    { value: 'validating', label: 'Memvalidasi' },
    { value: 'success', label: 'Berhasil' },
    { value: 'failed', label: 'Gagal' },
];

const VALIDATION_OPTIONS = [
    { value: '', label: 'Semua validasi' },
    { value: 'pending', label: 'Belum divalidasi' },
    { value: 'passed', label: 'Lolos validasi' },
    { value: 'failed', label: 'Tidak valid' },
];

export function BackupFilters({
    filters,
    onChange,
    onReset,
}: {
    filters: Filters;
    onChange: (next: Partial<Filters>) => void;
    onReset: () => void;
}) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<Filters>(filters);

    useEffect(() => {
        if (open) {
            setDraft(filters);
        }
    }, [open, filters]);

    const hasFilter =
        filters.search !== '' ||
        filters.type !== '' ||
        filters.source !== '' ||
        filters.status !== '' ||
        filters.validasi !== '' ||
        filters.dari !== '' ||
        filters.sampai !== '';

    const activeFilterCount = [
        filters.type,
        filters.source,
        filters.status,
        filters.validasi,
        filters.dari,
        filters.sampai,
    ].filter(Boolean).length;

    const applyDraft = () => {
        setOpen(false);
        onChange(draft);
    };

    const resetDraft = () => {
        const next: Filters = {
            ...filters,
            type: '',
            source: '',
            status: '',
            validasi: '',
            dari: '',
            sampai: '',
        };

        setDraft(next);
        setOpen(false);
        onChange(next);
    };

    return (
        <>
            <ListToolbar
                search={filters.search}
                onSearch={(search) => onChange({ search })}
                searchPlaceholder="Cari nama file, UUID, atau catatan"
            >
                <Button
                    type="button"
                    variant="secondary"
                    onClick={() => setOpen(true)}
                >
                    <SlidersHorizontal className="size-4" aria-hidden="true" />
                    Filter
                    {activeFilterCount > 0 ? (
                        <Badge variant="secondary">{activeFilterCount}</Badge>
                    ) : null}
                </Button>

                {hasFilter ? <ResetFilters onReset={onReset} /> : null}
            </ListToolbar>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Filter Backup</DialogTitle>
                        <DialogDescription>
                            Pilih jenis, sumber, status, validasi, dan rentang
                            tanggal backup.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <FilterSelect
                            id="filter-type"
                            label="Jenis backup"
                            value={draft.type}
                            onChange={(type) =>
                                setDraft((current) => ({ ...current, type }))
                            }
                            options={TYPE_OPTIONS}
                        />
                        <FilterSelect
                            id="filter-source"
                            label="Sumber backup"
                            value={draft.source}
                            onChange={(source) =>
                                setDraft((current) => ({
                                    ...current,
                                    source,
                                }))
                            }
                            options={SOURCE_OPTIONS}
                        />
                        <FilterSelect
                            id="filter-status"
                            label="Status backup"
                            value={draft.status}
                            onChange={(status) =>
                                setDraft((current) => ({
                                    ...current,
                                    status,
                                }))
                            }
                            options={STATUS_OPTIONS}
                        />
                        <FilterSelect
                            id="filter-validation"
                            label="Status validasi"
                            value={draft.validasi}
                            onChange={(validasi) =>
                                setDraft((current) => ({
                                    ...current,
                                    validasi,
                                }))
                            }
                            options={VALIDATION_OPTIONS}
                        />

                        <Field id="filter-from" label="Dari tanggal">
                            <DatePicker
                                id="filter-from"
                                value={draft.dari}
                                onChange={(dari) =>
                                    setDraft((current) => ({
                                        ...current,
                                        dari,
                                    }))
                                }
                                placeholder="Pilih tanggal awal"
                                className="h-11 w-full"
                            />
                        </Field>

                        <Field id="filter-to" label="Sampai tanggal">
                            <DatePicker
                                id="filter-to"
                                value={draft.sampai}
                                onChange={(sampai) =>
                                    setDraft((current) => ({
                                        ...current,
                                        sampai,
                                    }))
                                }
                                placeholder="Pilih tanggal akhir"
                                className="h-11 w-full"
                            />
                        </Field>
                    </div>

                    <DialogFooter className="mt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={resetDraft}
                        >
                            Reset
                        </Button>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button type="button" onClick={applyDraft}>
                            Terapkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
