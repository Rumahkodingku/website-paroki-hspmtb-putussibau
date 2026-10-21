import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ChoiceSelect, Field } from '@/components/form-fields';

/*
 * The admin's shared select, on the project's Radix Select rather than a bare
 * <select>.
 *
 * Two things about the swap are worth a test of their own, and neither is
 * visible from a screenshot:
 *
 * 1. The label still binds. SelectTrigger renders a <button>, not a <select>,
 * so the association is through htmlFor on a labelable element rather than
 * through a form control. getByLabelText is the assertion for it.
 *
 * 2. The empty value is translated on the way in and back on the way out.
 * Radix decides what to display by asking whether the value is "", which is
 * exactly how shouldShowPlaceholder is written — so a caller with a genuine ""
 * option (the notice picker's "Umum") would have its label replaced by the
 * placeholder. The sentinel is what keeps those two apart, and a test that
 * only checked a normal pick would never notice it had stopped working.
 */

const LOCATIONS = [
    { value: '1', label: 'Gereja Paroki' },
    { value: '2', label: 'Stasi Tanah Empang' },
];

/**
 * Radix renders into a portal and drives its list with layout measurements
 * happy-dom does not do, so a couple of the primitives are stubbed here. What
 * is left is the component's own logic: the sentinel, the label binding and
 * the value handed back.
 */
async function openList(): Promise<void> {
    await userEvent.click(screen.getByRole('combobox'));
}

describe('ChoiceSelect', () => {
    it('takes its accessible name from the field label', () => {
        render(
            <Field id="location" label="Lokasi">
                <ChoiceSelect
                    id="location"
                    value=""
                    onChange={() => {}}
                    options={LOCATIONS}
                    placeholder="Pilih lokasi"
                />
            </Field>,
        );

        expect(screen.getByLabelText('Lokasi')).toBeInTheDocument();
    });

    it('shows the placeholder until something is chosen', () => {
        render(
            <ChoiceSelect
                id="location"
                value=""
                onChange={() => {}}
                options={LOCATIONS}
                placeholder="Pilih lokasi"
            />,
        );

        expect(screen.getByRole('combobox')).toHaveTextContent('Pilih lokasi');
    });

    it('shows the label of the current value', () => {
        render(
            <ChoiceSelect
                id="location"
                value="2"
                onChange={() => {}}
                options={LOCATIONS}
                placeholder="Pilih lokasi"
            />,
        );

        expect(screen.getByRole('combobox')).toHaveTextContent(
            'Stasi Tanah Empang',
        );
    });

    it('offers the placeholder as a real, selectable option', async () => {
        const chosen: string[] = [];

        render(
            <ChoiceSelect
                id="location"
                value="1"
                onChange={(value) => chosen.push(value)}
                options={LOCATIONS}
                placeholder="Pilih lokasi"
            />,
        );

        await openList();

        await userEvent.click(
            screen.getByRole('option', { name: 'Pilih lokasi' }),
        );

        // Back to "" and not to the sentinel: the caller has no idea it exists.
        expect(chosen).toEqual(['']);
    });

    it('hands back the raw value, not the sentinel', async () => {
        const chosen: string[] = [];

        render(
            <ChoiceSelect
                id="location"
                value=""
                onChange={(value) => chosen.push(value)}
                options={LOCATIONS}
                placeholder="Pilih lokasi"
            />,
        );

        await openList();

        await userEvent.click(
            screen.getByRole('option', { name: 'Stasi Tanah Empang' }),
        );

        expect(chosen).toEqual(['2']);
    });

    it('keeps a genuine empty option visible instead of showing a placeholder', () => {
        // The notice target picker offers "Umum" with an empty value and no
        // placeholder prop. Before the sentinel, Radix would have rendered
        // the placeholder in its place and "Umum" would have been unreachable.
        render(
            <ChoiceSelect
                id="target"
                value=""
                onChange={() => {}}
                options={[
                    { value: '', label: 'Umum' },
                    { value: '1', label: 'Lokasi — Gereja Paroki' },
                ]}
            />,
        );

        expect(screen.getByRole('combobox')).toHaveTextContent('Umum');
    });

    it('adds no empty option when the caller asked for no placeholder', async () => {
        render(
            <ChoiceSelect
                id="target"
                value=""
                onChange={() => {}}
                options={[
                    { value: '', label: 'Umum' },
                    { value: '1', label: 'Lokasi — Gereja Paroki' },
                ]}
            />,
        );

        await openList();

        // Exactly the two given, and nothing invented.
        expect(screen.getAllByRole('option')).toHaveLength(2);
    });
});
