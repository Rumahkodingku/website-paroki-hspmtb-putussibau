import { useCurrentUrl } from '@/hooks/use-current-url';
import { sectionPrefix } from '@/layouts/public/navigation';
import type { NavEntry } from '@/layouts/public/navigation';

export function useNavActive() {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const isActive = (entry: NavEntry): boolean =>
        entry.match === 'exact'
            ? isCurrentUrl(entry.href)
            : entry.match === 'prefix'
              ? isCurrentOrParentUrl(sectionPrefix(entry.href))
              : isCurrentOrParentUrl(entry.href);

    return { isActive };
}
