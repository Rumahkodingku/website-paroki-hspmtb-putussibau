import type { InertiaLinkProps } from '@inertiajs/react';
import {
    CalendarClock,
    CalendarDays,
    DatabaseBackup,
    FileText,
    Globe,
    HandHeart,
    History,
    Images,
    ListChecks,
    MapPin,
    Settings,
    UserCog,
    UserRound,
    Users,
    type LucideIcon,
} from 'lucide-react';

import { edit as editSettings } from '@/routes/settings';
import {
    index as massSchedulesIndex,
    list as massSchedulesList,
} from '@/routes/mass-schedules';
import { index as locationsIndex } from '@/routes/locations';
import { index as specialMassesIndex } from '@/routes/special-masses';
import { index as scheduleNoticesIndex } from '@/routes/schedule-notices';
import { edit as editProfile } from '@/routes/profile';
import { list as postsList } from '@/routes/posts';
import { index as postCategoriesIndex } from '@/routes/post-categories';
import { list as eventsList } from '@/routes/events';
import { index as eventCategoriesIndex } from '@/routes/event-categories';
import { index as servicesIndex } from '@/routes/services';
import { index as serviceCategoriesIndex } from '@/routes/service-categories';
import { index as communitiesIndex } from '@/routes/communities';
import { index as communityCategoriesIndex } from '@/routes/community-categories';
import { index as galleriesIndex } from '@/routes/galleries';
import { index as documentsIndex } from '@/routes/documents';
import { index as documentCategoriesIndex } from '@/routes/document-categories';
import { index as areasIndex } from '@/routes/areas';
import { index as parishHistoriesIndex } from '@/routes/parish-histories';
import { edit as editParishProfile } from '@/routes/parish-profile';
import { edit as editServiceArea } from '@/routes/parish-service-area';
import { index as pastorsIndex } from '@/routes/pastors';
import { index as boardPeriodsIndex } from '@/routes/board-periods';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { index as backupsIndex } from '@/routes/backups';

export type Entry = {
    title: string;
    href?: InertiaLinkProps['href'];
    icon?: LucideIcon;
    children?: Entry[];
    ability?: string;
};

/**
 * Filter navigation entries by the server-resolved abilities.
 *
 * The backend remains the security boundary; this only keeps a menu item a user
 * cannot use out of their sidebar.
 */
export function visibleEntries(
    entries: Entry[],
    abilities: Record<string, boolean>,
): Entry[] {
    return entries
        .filter((entry) => !entry.ability || abilities[entry.ability] === true)
        .map((entry) =>
            entry.children
                ? {
                      ...entry,
                      children: visibleEntries(entry.children, abilities),
                  }
                : entry,
        )
        .filter((entry) => !entry.children || entry.children.length > 0);
}

export const CONTENT_ENTRIES: Entry[] = [
    {
        title: 'Profil Paroki',
        href: editParishProfile(),
        icon: UserRound,
        children: [
            { title: 'Informasi Profil', href: editParishProfile() },
            { title: 'Sejarah Paroki', href: parishHistoriesIndex() },
            { title: 'Wilayah Pelayanan', href: editServiceArea() },
            { title: 'Pastor Paroki', href: pastorsIndex() },
            { title: 'Struktur Kepengurusan', href: boardPeriodsIndex() },
        ],
    },
    {
        title: 'Jadwal Misa',
        href: massSchedulesIndex(),
        icon: CalendarDays,
        children: [
            {
                title: 'Misa Rutin',
                href: massSchedulesList(),
            },
            {
                title: 'Misa Khusus',
                href: specialMassesIndex(),
            },
            {
                title: 'Pemberitahuan',
                href: scheduleNoticesIndex(),
            },
        ],
    },
    { title: 'Berita', href: postsList(), icon: Globe },
    { title: 'Agenda', href: eventsList(), icon: CalendarClock },
    {
        title: 'Master Data',
        icon: ListChecks,
        children: [
            { title: 'Lokasi', href: locationsIndex() },
            { title: 'Kategori Berita', href: postCategoriesIndex() },
            { title: 'Kategori Agenda', href: eventCategoriesIndex() },
            { title: 'Kategori Pelayanan', href: serviceCategoriesIndex() },
            { title: 'Kategori Komunitas', href: communityCategoriesIndex() },
            { title: 'Kategori Dokumen', href: documentCategoriesIndex() },
        ],
    },
    { title: 'Pelayanan', href: servicesIndex(), icon: HandHeart },
    { title: 'Komunitas', href: communitiesIndex(), icon: Users },
    { title: 'Area', href: areasIndex(), icon: MapPin },
    { title: 'Galeri', href: galleriesIndex(), icon: Images },
    { title: 'Dokumen', href: documentsIndex(), icon: FileText },
];

export const ACCOUNT_ENTRIES: Entry[] = [
    {
        title: 'Pengaturan',
        href: editSettings(),
        icon: Settings,
    },
    {
        title: 'Akun',
        href: editProfile(),
        icon: UserCog,
    },
];

export const ADMINISTRATION_ENTRIES: Entry[] = [
    {
        title: 'Audit Log',
        href: auditLogsIndex(),
        icon: History,
        ability: 'auditlogs.view',
    },
    {
        title: 'Backup & Pemulihan',
        href: backupsIndex(),
        icon: DatabaseBackup,
        ability: 'backups.view',
    },
];
