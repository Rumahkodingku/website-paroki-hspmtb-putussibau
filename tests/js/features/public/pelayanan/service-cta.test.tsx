import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ServiceCta } from '@/features/public/pelayanan/components/service-cta';
import type {
    PublicServiceContact,
    PublicServiceCta,
} from '@/features/public/pelayanan/types';

const labels = {
    register_now: 'Daftar Sekarang',
    registration_closed: 'Pendaftaran Ditutup',
    contact_secretariat:
        'Silakan hubungi sekretariat paroki untuk informasi lebih lanjut.',
    contact_now: 'Hubungi Sekarang',
    call: 'Telepon',
    whatsapp: 'WhatsApp',
};

const contact: PublicServiceContact = {
    name: null,
    phone: null,
    tel: null,
    whatsapp: null,
    source: null,
};

function cta(overrides: Partial<PublicServiceCta> = {}): PublicServiceCta {
    return {
        state: 'contact',
        url: null,
        label: 'Daftar Sekarang',
        message: null,
        ...overrides,
    };
}

describe('ServiceCta', () => {
    it('renders registration button when form open', () => {
        render(
            <ServiceCta
                cta={cta({
                    state: 'form_open',
                    url: 'https://example.com/form',
                })}
                contact={contact}
                labels={labels}
            />,
        );
        expect(
            screen.getByRole('link', { name: /Daftar Sekarang/ }),
        ).toHaveAttribute('href', 'https://example.com/form');
    });

    it('renders disabled button when form closed', () => {
        render(
            <ServiceCta
                cta={cta({ state: 'form_closed' })}
                contact={contact}
                labels={labels}
            />,
        );
        expect(
            screen.getByRole('button', { name: 'Pendaftaran Ditutup' }),
        ).toBeDisabled();
    });

    it('renders secretariat message when no contact', () => {
        render(
            <ServiceCta
                cta={cta({ state: 'contact' })}
                contact={contact}
                labels={labels}
            />,
        );
        expect(
            screen.getByText(
                'Silakan hubungi sekretariat paroki untuk informasi lebih lanjut.',
            ),
        ).toBeInTheDocument();
    });
});
