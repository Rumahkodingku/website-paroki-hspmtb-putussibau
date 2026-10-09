import { Text } from '@/components/text';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: User;
    showEmail?: boolean;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarFallback className="rounded-lg bg-surface-chip text-ink dark:bg-secondary dark:text-foreground">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            {/*
                The container keeps text-sm leading-tight because that is a
                two-line stack and the tightness is what makes it one; the two
                lines inside it are named roles. name is body-strong (the
                user's own name is the strongest thing in a 32px avatar row)
                and email is fine-print, which is two steps below and reads as
                the secondary line without needing muted to do all the work.
            */}
            <div className="grid flex-1 text-left text-sm leading-tight">
                <Text as="span" variant="body-strong" truncate>
                    {user.name}
                </Text>
                {showEmail && (
                    <Text as="span" variant="fine-print" color="muted" truncate>
                        {user.email}
                    </Text>
                )}
            </div>
        </>
    );
}
