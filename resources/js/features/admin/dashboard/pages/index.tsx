import { Head, usePage } from '@inertiajs/react';
import type { SharedProps } from '@/types';
import { dashboard } from '@/routes';
import { AttentionPanel } from '../components/attention-panel';
import { DashboardHeader } from '../components/dashboard-header';
import { RecentContentPanel } from '../components/recent-content-panel';
import { RecentPostsPanel } from '../components/recent-posts-panel';
import { SummaryCards } from '../components/summary-cards';
import { UpcomingEventsPanel } from '../components/upcoming-events-panel';
import type { DashboardProps } from '../types';

export default function Dashboard({
    summary,
    upcomingEvents,
    recentPosts,
    attentionItems,
    recentContent,
}: DashboardProps) {
    const { auth, displayTimezone } = usePage<SharedProps>().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto w-full max-w-[1600px] space-y-6 p-4 sm:p-6 xl:space-y-8 xl:p-8">
                <h1 className="sr-only">Dashboard</h1>

                {/* Welcome and primary actions */}
                <DashboardHeader name={auth.user?.name ?? null} />

                {/* Overview metrics */}
                <section aria-label="Ringkasan pengelolaan paroki">
                    <SummaryCards summary={summary} />
                </section>

                {/* Main workspace */}
                <div className="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
                    {/* Upcoming events */}
                    <section
                        aria-label="Agenda terdekat"
                        className="min-w-0 xl:col-span-7"
                    >
                        <UpcomingEventsPanel
                            events={upcomingEvents}
                            displayTimezone={displayTimezone}
                            className="h-full"
                        />
                    </section>

                    {/* Latest posts */}
                    <section
                        aria-label="Berita terbaru"
                        className="min-w-0 xl:col-span-5"
                    >
                        <RecentPostsPanel
                            posts={recentPosts}
                            displayTimezone={displayTimezone}
                            className="h-full"
                        />
                    </section>

                    {/* Attention and follow-up items */}
                    <section
                        aria-label="Perlu perhatian"
                        className="min-w-0 xl:col-span-5"
                    >
                        <AttentionPanel
                            items={attentionItems}
                            className="h-full"
                        />
                    </section>

                    {/* Recently updated content */}
                    <section
                        aria-label="Konten terbaru"
                        className="min-w-0 xl:col-span-7"
                    >
                        <RecentContentPanel
                            items={recentContent}
                            displayTimezone={displayTimezone}
                            className="h-full"
                        />
                    </section>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
