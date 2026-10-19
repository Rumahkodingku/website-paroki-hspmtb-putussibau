import { Head, usePage } from '@inertiajs/react';
import type { SharedProps } from '@/types';
import { dashboard } from '@/routes';
import { AttentionPanel } from '../components/attention-panel';
import { DashboardHeader } from '../components/dashboard-header';
import { QuickActions } from '../components/quick-actions';
import { RecentContentPanel } from '../components/recent-content-panel';
import { RecentPostsPanel } from '../components/recent-posts-panel';
import { SummaryCards } from '../components/summary-cards';
import { UpcomingEventsPanel } from '../components/upcoming-events-panel';
import type { DashboardProps } from '../types';

/*
 * The admin dashboard.
 *
 * A read-only aggregator: the controller does the counting and the bounding,
 * and this page lays the results out. Section order follows the roadmap — a
 * greeting, the five module summaries, the shortcuts beside the next few
 * activities, then the newest articles beside what needs attention, and finally
 * the merged content feed.
 *
 * Every panel renders an empty state, so a fresh installation with no content
 * shows a composed page of zeros rather than an error.
 */
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

            <div className="space-y-6 p-6">
                <h1 className="sr-only">Dashboard</h1>

                <DashboardHeader name={auth.user?.name ?? null} />

                <SummaryCards summary={summary} />

                <div className="grid gap-6 lg:grid-cols-3">
                    <QuickActions />
                    <UpcomingEventsPanel
                        events={upcomingEvents}
                        displayTimezone={displayTimezone}
                        className="lg:col-span-2"
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <RecentPostsPanel
                        posts={recentPosts}
                        displayTimezone={displayTimezone}
                    />
                    <AttentionPanel items={attentionItems} />
                </div>

                <RecentContentPanel
                    items={recentContent}
                    displayTimezone={displayTimezone}
                />
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
