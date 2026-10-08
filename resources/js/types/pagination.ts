/**
 * The shape a paginated Inertia prop arrives in.
 *
 * Not a project invention: it is what `JsonResource::collection($paginator)`
 * serialises to, which is `data` plus `links` plus `meta`. The three keys have
 * to be named exactly this way because Inertia passes the array through
 * unchanged — there is no mapping step that could absorb a different name.
 *
 * ARCHITECTURE.md Part C section 4 specifies this shape, and this type is where
 * that specification becomes checkable. Nothing reads it yet: Phase 01 has no
 * paginated list, and `app/` contains no call to paginate() yet either. It is
 * here so that the first list to be built has a contract to write against,
 * rather than inventing one and discovering the mismatch in a rendered page.
 *
 * The link and label arrays are Laravel's, not ours. `meta.links` is the
 * window of page links to render, and each entry already carries its own `url`,
 * so a list should read it rather than composing URLs by arithmetic.
 *
 * @see docs/ARCHITECTURE.md Part C section 4
 */
export type PaginatedLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        /** Per-page defaults to 15 unless the controller asks for something else. */
        per_page: number;
        to: number | null;
        total: number;
        links: PaginatedLink[];
    };
};
