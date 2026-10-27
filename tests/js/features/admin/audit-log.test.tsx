import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { AuditDiff } from '@/features/admin/audit-log/components/audit-diff';
import { visibleEntries, type Entry } from '@/layouts/app/app-sidebar-nav';

describe('visibleEntries', () => {
    const entries: Entry[] = [
        { title: 'Berita', href: '/admin/berita' },
        {
            title: 'Audit Log',
            href: '/admin/audit-log',
            ability: 'auditlogs.view',
        },
        {
            title: 'Administrasi',
            children: [
                {
                    title: 'Audit Log',
                    href: '/admin/audit-log',
                    ability: 'auditlogs.view',
                },
            ],
        },
    ];

    it('hides an entry the user cannot access', () => {
        const visible = visibleEntries(entries, {});

        expect(visible.map((entry) => entry.title)).toEqual(['Berita']);
    });

    it('shows an entry the server allowed', () => {
        const visible = visibleEntries(entries, { 'auditlogs.view': true });

        expect(visible.map((entry) => entry.title)).toContain('Audit Log');
        expect(visible[2]?.children).toHaveLength(1);
    });
});

describe('AuditDiff', () => {
    it('renders only changed fields and treats null as empty', () => {
        render(
            <AuditDiff
                before={{ title: 'Lama', status: null }}
                after={{ title: 'Baru', status: null }}
            />,
        );

        expect(screen.getByText('title')).toBeInTheDocument();
        expect(screen.queryByText('status')).not.toBeInTheDocument();
        expect(screen.getByText('Lama')).toBeInTheDocument();
        expect(screen.getByText('Baru')).toBeInTheDocument();
    });

    it('escapes HTML as text rather than rendering it', () => {
        render(
            <AuditDiff
                before={{ title: '<script>alert(1)</script>' }}
                after={{ title: '<b>aman</b>' }}
            />,
        );

        expect(
            screen.getByText('<script>alert(1)</script>'),
        ).toBeInTheDocument();
        expect(document.querySelector('script')).toBeNull();
    });

    it('shows an empty state when nothing changed', () => {
        render(
            <AuditDiff before={{ title: 'Sama' }} after={{ title: 'Sama' }} />,
        );

        expect(
            screen.getByText('Tidak ada field yang berubah pada snapshot ini.'),
        ).toBeInTheDocument();
    });
});
