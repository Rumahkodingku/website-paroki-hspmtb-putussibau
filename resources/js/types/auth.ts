/**
 * The authenticated user as shared by HandleInertiaRequests.
 *
 * This mirrors the `->only([...])` list in that middleware exactly. It is
 * intentionally closed: there is no index signature, so reading a field the
 * backend does not send is a type error rather than a silent `undefined` that
 * only shows up at runtime.
 *
 * @see docs/DECISIONS.md D-21
 */
export type AuthUser = {
    id: number;
    name: string;
    email: string;
    /** ISO 8601, or null when the address is not verified yet. */
    email_verified_at: string | null;
};

/**
 * A user rendered by a page that needs more than the shared shape. Pages should
 * shape their own props rather than widening this type.
 */
export type User = AuthUser;

export type Auth = {
    /** null for guests. Never omit the key. */
    user: AuthUser | null;
};
