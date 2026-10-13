import { Text } from '@/components/text';
import type { PublicBoardMember } from '../types';

/*
 * OrganizationChart - DESIGN.md, "Profile Components".
 *
 * DESIGN.md asks for accessible HTML relationships rather than a purely visual
 * diagram, so this is a semantic list of members in the admin's order, not an
 * absolutely-positioned chart. A screen reader gets the same structure a sighted
 * visitor sees.
 */
export function OrganizationChart({
    members,
}: {
    members: PublicBoardMember[];
}) {
    if (members.length === 0) {
        return null;
    }

    return (
        <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {members.map((member) => (
                <li
                    key={member.id}
                    className="flex items-center gap-4 rounded-lg border border-hairline bg-background p-4"
                >
                    {member.photo ? (
                        <img
                            src={member.photo}
                            alt={member.photo_alt ?? member.name}
                            loading="lazy"
                            className="size-14 shrink-0 rounded-full border border-hairline object-cover"
                        />
                    ) : null}

                    <div>
                        <Text as="p" variant="body-strong">
                            {member.name}
                        </Text>
                        <Text as="p" variant="caption" color="muted">
                            {member.position}
                        </Text>
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default OrganizationChart;
