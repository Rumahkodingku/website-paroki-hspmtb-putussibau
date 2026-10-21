import { Text } from '@/components/text';
import { Church, FileText, UserRound, Users } from 'lucide-react';
import type { PublicBoardMember } from '../types';

function sortMembers(members: PublicBoardMember[]): PublicBoardMember[] {
    return [...members].sort((a, b) => a.sort_order - b.sort_order);
}

function MemberAvatar({
    member,
    featured = false,
}: {
    member: PublicBoardMember;
    featured?: boolean;
}) {
    return (
        <div
            className={[
                'relative shrink-0 overflow-hidden rounded-full bg-muted',
                featured ? 'size-24 sm:size-28' : 'size-14 sm:size-16',
            ].join(' ')}
        >
            {member.photo ? (
                <img
                    src={member.photo}
                    alt={member.photo_alt ?? `Foto ${member.name}`}
                    loading="lazy"
                    decoding="async"
                    className="size-full object-cover object-top"
                />
            ) : (
                <div
                    aria-hidden="true"
                    className="flex size-full items-center justify-center text-muted-foreground"
                >
                    <UserRound
                        className={featured ? 'size-10' : 'size-6'}
                        strokeWidth={1.5}
                    />
                </div>
            )}
        </div>
    );
}

function MemberPosition({
    position,
    featured = false,
}: {
    position: string;
    featured?: boolean;
}) {
    return (
        <span
            className={[
                'inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1',
                featured
                    ? 'bg-primary/10 text-primary'
                    : 'bg-muted text-muted-foreground',
            ].join(' ')}
        >
            {featured ? (
                <Church className="size-3.5" aria-hidden="true" />
            ) : (
                <Users className="size-3.5" aria-hidden="true" />
            )}

            <Text as="span" variant="label" color="inherit" weight="medium">
                {position}
            </Text>
        </span>
    );
}

function MemberDescription({
    description,
}: {
    description: PublicBoardMember['description'];
}) {
    if (!description) {
        return null;
    }

    return (
        <div className="flex items-start gap-2">
            <FileText
                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />

            <Text as="p" variant="body-sm" color="muted" className="min-w-0">
                {description}
            </Text>
        </div>
    );
}

export function OrganizationChart({
    members,
}: {
    members: PublicBoardMember[];
}) {
    const sortedMembers = sortMembers(members);

    const chairperson = sortedMembers.find(
        (member) => member.position.trim().toLowerCase() === 'ketua',
    );

    const otherMembers = sortedMembers.filter(
        (member) => member.id !== chairperson?.id,
    );

    if (sortedMembers.length === 0) {
        return (
            <Text
                as="p"
                variant="body"
                color="muted"
                className="rounded-xl border border-dashed border-border p-6"
            >
                Data pengurus pada periode ini belum dipublikasikan.
            </Text>
        );
    }

    return (
        <div className="space-y-5">
            {/* Ketua sebagai profil unggulan */}
            {chairperson && (
                <article className="relative overflow-hidden">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0"
                    />

                    <div className="relative grid gap-5 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_minmax(220px,0.65fr)] lg:items-center lg:gap-8">
                        <div className="flex min-w-0 flex-col gap-5 sm:flex-row sm:items-center">
                            <MemberAvatar member={chairperson} featured />

                            <div className="min-w-0 space-y-3">
                                <MemberPosition
                                    position={chairperson.position}
                                    featured
                                />

                                <Text
                                    as="h3"
                                    variant="h3"
                                    className="wrap-break-word"
                                >
                                    {chairperson.name}
                                </Text>

                                <div className="flex items-center gap-2">
                                    <UserRound
                                        className="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />

                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        Pengurus inti
                                    </Text>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            )}

            {/* Pengurus lainnya */}
            {otherMembers.length > 0 && (
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {otherMembers.map((member) => (
                        <article
                            key={member.id}
                            className="flex min-w-0 items-center gap-4 p-4 transition-colors hover:bg-muted/40 sm:p-5"
                        >
                            <MemberAvatar member={member} />

                            <div className="min-w-0 flex-1 space-y-2">
                                <MemberPosition position={member.position} />

                                <Text
                                    as="h4"
                                    variant="h4"
                                    className="wrap-break-word"
                                >
                                    {member.name}
                                </Text>

                                {member.description && (
                                    <MemberDescription
                                        description={member.description}
                                    />
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            )}

            <div className="flex items-center gap-2 pt-1">
                <Users
                    className="size-4 text-muted-foreground"
                    aria-hidden="true"
                />

                <Text as="p" variant="body-sm" color="muted">
                    {sortedMembers.length} pengurus dalam periode ini
                </Text>
            </div>
        </div>
    );
}

export default OrganizationChart;
