import { ExternalLink } from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

/*
 * The dashboard's opening line.
 *
 * The greeting uses the signed-in account's own name, which arrives on the
 * shared `auth` prop. It is not stored on the dashboard and it is not personal
 * data beyond what the admin already sees in the sidebar.
 *
 * "Lihat Website" opens the public site in a new tab so the admin keeps the
 * dashboard behind them while they check what a visitor sees.
 */
export function DashboardHeader({ name }: { name: string | null }) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="space-y-1">
                <Text as="h2" variant="heading-md">
                    {name ? `Selamat datang, ${name}` : 'Selamat datang'}
                </Text>
                <Text as="p" variant="body-sm" color="muted">
                    Ringkasan konten, agenda, dan hal yang perlu ditindaklanjuti
                    di website paroki.
                </Text>
            </div>

            <Button asChild variant="secondary">
                <a href={home().url} target="_blank" rel="noreferrer">
                    <ExternalLink aria-hidden="true" />
                    Lihat Website
                </a>
            </Button>
        </div>
    );
}
