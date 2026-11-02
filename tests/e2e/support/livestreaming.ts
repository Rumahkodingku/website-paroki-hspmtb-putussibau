import { execFileSync } from 'node:child_process';
import { e2eEnvironment } from './environment';

/*
 * Removes the records this spec creates, so a targeted re-run does not meet a
 * second row with the same title. `migrate:fresh` already runs in
 * `app:e2e:prepare`; this is the cheaper safety net for `npx playwright test`
 * against an already-prepared database.
 */

const SCRIPT = String.raw`
\App\Models\Livestream::withTrashed()
    ->where('title', 'like', 'E2E Livestream%')
    ->get()
    ->each
    ->forceDelete();

$location = \App\Models\Location::withTrashed()
    ->where('slug', 'gereja-paroki-e2e')
    ->first();

if ($location === null) {
    \App\Models\Location::factory()->create([
        'name' => 'Gereja Paroki E2E',
        'slug' => 'gereja-paroki-e2e',
        'is_active' => true,
    ]);
} else {
    $location->restore();
    $location->update([
        'name' => 'Gereja Paroki E2E',
        'is_active' => true,
    ]);
}
`;

export function resetLivestreamFixtures(): void {
    const environment = e2eEnvironment();

    execFileSync('php', ['artisan', 'tinker', '--execute', SCRIPT], {
        env: { ...process.env, DB_DATABASE: environment.database },
        stdio: 'ignore',
    });
}
