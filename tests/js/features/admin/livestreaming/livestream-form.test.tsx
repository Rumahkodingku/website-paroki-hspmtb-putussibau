import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    LivestreamForm,
    emptyLivestreamFormData,
    submitLivestreamForm,
} from '@/features/admin/livestreaming/components/livestream-form';
import type { LivestreamFormInstance } from '@/features/admin/livestreaming/components/livestream-form-instance';
import type { LivestreamFormData } from '@/features/admin/livestreaming/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The livestream form.
 *
 * Two things are load-bearing here: a server rejection lands on the field that
 * caused it, and the two datetime-local values are converted to ISO instants
 * before the request leaves the browser.
 */

const LABELS: Record<string, string> = {
    title: 'Judul Livestreaming',
    youtube_url: 'URL YouTube',
    description: 'Deskripsi',
    category: 'Kategori',
    location: 'Lokasi',
    scheduled_at: 'Jadwal Siaran',
    status: 'Status Publikasi',
    recorded_at: 'Waktu Rekaman',
    is_recording_available: 'Rekaman Tersedia',
};

const HINTS: Record<string, string> = {
    youtube_url: 'Tempel tautan video YouTube.',
    is_recording_available: 'Centang setelah replay tersedia.',
};

const CATEGORIES = [
    { value: 'liturgi', label: 'Liturgi' },
    { value: 'hari_raya', label: 'Hari Raya' },
];

const STATUSES = [
    { value: 'draft', label: 'Draf' },
    { value: 'published', label: 'Terbit' },
    { value: 'archived', label: 'Diarsipkan' },
];

const LOCATIONS = [
    { id: 1, name: 'Gereja Paroki', type: 'gereja_paroki' },
    { id: 2, name: 'Kapel Santo Paulus', type: 'kapel' },
];

function renderForm(
    errors: Record<string, string>,
    initial: Partial<LivestreamFormData> = {},
) {
    const onSubmit = (form: LivestreamFormInstance) => {
        form.setError(errors);
    };

    renderWithInertia(
        <LivestreamForm
            initial={{ ...emptyLivestreamFormData(), ...initial }}
            labels={LABELS}
            hints={HINTS}
            copy={{}}
            categories={CATEGORIES}
            statuses={STATUSES}
            locations={LOCATIONS}
            submitLabel="Simpan livestreaming"
            onSubmit={onSubmit}
            onCancel={() => undefined}
        />,
        { url: '/admin/livestreaming/tambah' },
    );

    return {
        submit: () =>
            userEvent.click(
                screen.getByRole('button', { name: 'Simpan livestreaming' }),
            ),
    };
}

describe('LivestreamForm', () => {
    it('renders the labels and hints it is handed', () => {
        renderForm({});

        expect(screen.getByLabelText(/Judul Livestreaming/)).toBeVisible();
        expect(screen.getByLabelText(/URL YouTube/)).toBeVisible();
        expect(screen.getByLabelText(/Lokasi/)).toBeVisible();
        expect(screen.getByLabelText(/Jadwal Siaran/)).toBeVisible();
        expect(
            screen.getByText('Centang setelah replay tersedia.'),
        ).toBeVisible();
    });

    it('shows a server error next to the field that caused it', async () => {
        const { submit } = renderForm({
            youtube_url: 'Tautan video harus berupa tautan YouTube yang valid.',
        });

        await submit();

        expect(
            screen.getByText(
                'Tautan video harus berupa tautan YouTube yang valid.',
            ),
        ).toBeVisible();
    });

    it('converts datetime-local values to ISO instants before submitting', () => {
        const transform = vi.fn();
        const submit = vi.fn();
        const form = {
            transform,
            submit,
        } as unknown as LivestreamFormInstance;

        submitLivestreamForm(form, {
            url: '/admin/livestreaming',
            method: 'post',
        });

        expect(transform).toHaveBeenCalledTimes(1);
        expect(submit).toHaveBeenCalledWith({
            url: '/admin/livestreaming',
            method: 'post',
        });

        const transformData = transform.mock.calls[0][0] as (
            data: LivestreamFormData,
        ) => Record<string, unknown>;

        const result = transformData({
            ...emptyLivestreamFormData(),
            scheduled_at: '2026-11-08T08:00',
            recorded_at: '',
        });

        expect(result.scheduled_at).toBe(
            new Date('2026-11-08T08:00').toISOString(),
        );
        expect(result.recorded_at).toBeNull();
    });
});
