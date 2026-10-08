import { readFileSync } from 'node:fs';
import { join } from 'node:path';

export interface E2eEnvironment {
    email: string;
    password: string;
    database: string;
}

/**
 * Read the environment AppE2ePrepare built.
 *
 * The file is written by `php artisan app:e2e:prepare` rather than filled in
 * here, because three things have to agree and two of them are PHP: the account
 * that was seeded, the database it was seeded into, and the database the web
 * server is pointed at. A second copy of any of them would drift — override
 * E2E_ADMIN_PASSWORD and the specs would keep trying the old one, and the
 * failure would read like a broken login flow rather than a stale fixture.
 */
export function e2eEnvironment(): E2eEnvironment {
    const file = join(import.meta.dirname, '..', '.auth.json');

    let parsed: unknown;

    try {
        parsed = JSON.parse(readFileSync(file, 'utf8'));
    } catch {
        throw new Error(
            `Missing ${file}. Run \`npm run e2e\`, which prepares the test ` +
                'database first, or `npm run e2e:prepare` if it already is.',
        );
    }

    const candidate = parsed as Partial<E2eEnvironment> | null;

    if (
        typeof candidate?.email !== 'string' ||
        typeof candidate?.password !== 'string' ||
        typeof candidate?.database !== 'string'
    ) {
        throw new Error(
            `${file} does not contain an email, a password and a database.`,
        );
    }

    return {
        email: candidate.email,
        password: candidate.password,
        database: candidate.database,
    };
}
