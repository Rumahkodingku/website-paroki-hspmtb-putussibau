# Development

Everything needed to get this repository running on a new machine, and the few
places where the local setup differs from what a reasonable person would assume.

For *how to build things*, read `ARCHITECTURE.md`. For *why a choice was made*,
read `docs/DECISIONS.md` and `docs/roadmap/phase-01-project-foundation.md`. This
file is only about getting work done.

---

## 1. Prerequisites

| Tool | Version | Notes |
| --- | --- | --- |
| PHP | 8.3 | With `pdo_mysql`, `gd`, `mbstring`, `intl`, `fileinfo` |
| Composer | 2.x | |
| Node | 22.18+, 24.11+ or 26+ | Required by vite-plus; CI uses 22 |
| npm | 11+ | This project uses npm, not pnpm or yarn |
| MySQL | 8 | Native install. **There is no Docker**, see D-15 |

MySQL 8 runs natively on purpose. Docker was considered at P04 and cancelled:
the MySQL service image plus a health check costs more wall-clock time per CI run
than it saves, and the collation this project needs
(`utf8mb4_0900_ai_ci`) is MySQL-specific anyway.

Check the extensions before anything else, because a missing `gd` fails much
later and much less legibly:

```bash
php -m | grep -E 'pdo_mysql|gd|mbstring|intl|fileinfo'
```

---

## 2. First-time setup

```bash
composer setup
```

That is `composer install`, copy `.env.example` to `.env`, `key:generate`,
`migrate --force`, `npm install`, `npm run build`.

### 2.1 The test database

`composer setup` migrates the database in `.env`. Tests do **not** use it — they
use a second database, because `RefreshDatabase` runs `migrate:fresh` and would
otherwise wipe your working data on every run.

Create it once per machine:

```bash
mysql -u root -p -e "CREATE DATABASE website_paroki_hspmtb_test \
  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
```

The name is pinned in `phpunit.xml` for the PHP suite and in `config/e2e.php`
for the Playwright suite. Keep those two in step if you ever rename it.

> `php artisan db:create` does not exist in Laravel 13. The statement above is
> the whole setup step.

### 2.2 Environment variables

`.env.example` is a working local setup except for three things:

| Variable | Change it because |
| --- | --- |
| `APP_URL` | It is used to build canonical and Open Graph URLs. `http://localhost:8000` is correct only locally. |
| `ADMIN_EMAIL` | The committed value is a placeholder that looks like a real account. Use your own. |
| `ADMIN_PASSWORD` | The committed value is a throwaway. Use your own. |

`ADMIN_*` only matters on a fresh install: `SuperAdminSeeder` creates the first
Super Admin from them, and does nothing when they are blank (D-17). To add an
account later, change the values and re-run the seeder.

Never commit a real `.env`. Never put credentials in `phpunit.xml` either.

---

---

## 3. Database

### 3.1 Migrations

```bash
php artisan migrate                       # apply what is pending
php artisan migrate:status                # what has run
php artisan migrate:rollback              # undo the last batch
```

**Never edit a migration that has already run.** A new schema change is a new
migration, always. An edited old migration runs on nobody's database and
fails on yours, and the failure reads as a broken app rather than as two
developers' histories having diverged.

```bash
php artisan make:migration add_published_at_to_posts_table
```

The test database is migrated from scratch on every test run by
`RefreshDatabase`, so it never needs a migration applied by hand.

### 3.2 Seeders

`php artisan db:seed` runs all three, in this order:

| Seeder | What it does |
| --- | --- |
| `PermissionSeeder` | Creates the `super_admin` role and the fourteen permissions. Idempotent. |
| `SiteSettingsSeeder` | Creates two rows: `parish_name` and `parish_short_name`. |
| `SuperAdminSeeder` | Creates the first Super Admin **only if** `ADMIN_NAME`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` are set. |

The Super Admin seeder's silence when those are blank is deliberate (D-17): a
teammate who has not filled in its own `.env`, and CI, both run
`migrate:fresh --seed` without them and must not fail because of it.

**Nothing else is seeded, and nothing may be.** The starter kit shipped a "Test
User"; that is invented data and this project does not create invented parish
data. Every other setting falls back to its configured default in
`config/site-settings.php` and has no row at all.

Run one seeder on its own:

```bash
php artisan db:seed --class=PermissionSeeder
```

### 3.3 Docker

**There is no Docker in this project.** Do not add it, and do not look for a
`docker-compose.yml`: there is none.

It was considered as workstream 04 and cancelled (D-15). The MySQL service image
plus a health check cost more wall-clock time per CI run than they saved, and
the collation this project needs, `utf8mb4_0900_ai_ci`, is MySQL-specific — so
running anything other than MySQL 8 would not have been testing what production
runs anyway.

MySQL runs natively. If it is not installed, install MySQL 8; there is no
container to fall back to.

## 4. Running the site

```bash
composer dev
```

This is `php artisan dev` (Laravel Pao), which starts **five** processes:

| Process | What it does |
| --- | --- |
| web | `php artisan serve` |
| queue | the default queue worker |
| logs | `php artisan pail` |
| vite | `npm run dev` |
| scheduler | `php artisan schedule:work` |

The scheduler process is here rather than in the cron file so that a scheduled
task actually runs while you are developing. Production uses a cron line with
`schedule:run` instead — see the deployment notes in `docs/DECISIONS.md` D-12.

Frontend only, without the PHP processes: `npm run dev`.

---

## 5. The gate

```bash
composer ci:check
```

Five things, in this order:

| Step | Command | Cost |
| --- | --- | --- |
| format + lint | `npm run check` | ~1s |
| types | `npm run types:check` | ~2s |
| frontend unit tests | `npm run test:unit` | ~1.5s |
| Pint | `composer lint:check` | ~1s |
| PHPStan | `composer types:check` | ~5s |
| PHP tests | `php artisan test` | ~15s |

**MySQL must be running** or the last step fails. That is the whole cost of
adding the frontend suite: the gate is about 25 seconds.

`npm run build` is deliberately *not* in the gate. It is the slowest step and a
broken bundle is rare enough that `composer setup` and CI catch it.

### 5.1 The commit hook

`pre-commit` runs `lint-staged` and then the full `composer ci:check`. So:

- **A commit needs MySQL up** and takes about 25 seconds.
- A commit that only touches documentation still pays all 25 seconds.
- `commit-msg` runs commitlint. A conventional subject is mandatory.

Escape hatches, and when they are legitimate:

```bash
git commit --no-verify      # skip both hooks
git revert <sha>            # needs --no-verify; the generated message is not conventional
```

`--no-verify` is fine for a WIP commit on a branch you have pushed. It is not a
way to land a red test.

### 5.2 Commit messages

```
type(scope): imperative summary

Why the change, and what it rules out. Wrapped at 72 characters.
```

Allowed types include `feat`, `fix`, `docs`, `test`, `refactor`, `chore`, `ci`.
The subject must be at most 100 characters — the current longest is 83. A pull
request title is validated the same way, because a squash merge takes its
message from the title.

CI re-checks the whole commit range and the PR title, so `--no-verify` does not
get you past the rules, only past the delay.

---

## 6. Tests

### 6.1 PHP — Pest

```bash
php artisan test                                    # everything
php artisan test tests/Feature/Auth                 # one folder
php artisan test --filter=two_factor                # one behaviour
```

277 tests. `tests/Feature` gets `RefreshDatabase` automatically via
`tests/Pest.php`; do not add it by hand.

Tests that need roles call `seedRolesAndPermissions()` from `tests/Pest.php`,
which also drops Spatie's permission cache. Skipping that call is the usual cause
of a test that passes alone and fails in a suite.

### 6.2 Frontend unit and component — Vitest

```bash
npm run test:unit          # once
npm run test:unit:watch    # on change
```

Eleven tests, in `tests/js/`. `vp test` **is** Vitest — it is already installed
inside `vite-plus`, so there is no `vitest` dependency to add, and no
`vitest.config.ts`: the config is the `test` block in `vite.config.ts`.

Environment is `happy-dom` and globals are off, so `tests/js/setup.ts` calls
`cleanup()` itself. Import test APIs from `vite-plus/test`, not from a global.

Two things that cost an hour if you do not know them:

- **`tsconfig.json` must list your test path.** Its `include` is not a
  glob over everything; a test file outside it is invisible to both `tsc` and
  the type-aware lint.
- **Vitest's default glob is `**/*.spec.*`.** The Playwright specs in
  `tests/e2e/` match it, so `include` is narrowed to `tests/js/**` and
  `tests/e2e/**` is in `exclude`. Do not widen `include` casually.

An `<input type="password">` has no implicit ARIA role, so `getByRole('textbox')`
can never match one. Query it by label.

### 6.3 Frontend end-to-end — Playwright

```bash
npx playwright install chromium   # once per machine
npm run e2e                       # prepare the database, then run
```

Thirteen specs in `tests/e2e/`, against a real Chromium and a real server.

`npm run e2e` runs `php artisan app:e2e:prepare` first, which rebuilds
`website_paroki_hspmtb_test`, seeds a Super Admin, and writes
`tests/e2e/.auth.json` with the credentials and the database name. **It never
reads the database from `.env`** — it repoints the connection at
`config('e2e.database')` before migrating anything and prints the database it
ignored. Your development database is not at risk, and you do not need
`DB_DATABASE` set.

Things worth knowing before you debug a failure:

- The web server runs with **`APP_DEBUG=false`**. With debug on, Laravel's own
  debug page replaces the Inertia error pages and the specs pass against the
  wrong thing.
- `php artisan serve` handles one request at a time, so `workers: 1`. Parallel
  workers would queue and look like timeouts.
- The login rate limiter lives in the shared database cache, so every spec
  clears it first. Without that, the wrong-password spec locks out the login in
  the specs after it.
- First run downloads about 120 MB of Chromium.

Run one spec while iterating:

```bash
npx playwright test --grep "keluar"
```

`playwright.config.ts` reads `tests/e2e/.auth.json` at load time, so running
`npx playwright test` without preparing first fails with a message telling you
to run `npm run e2e`.

---

## 7. Generated files

Never edit these. They are regenerated and gitignored:

| Path | Regenerated by |
| --- | --- |
| `resources/js/routes/`, `resources/js/actions/`, `resources/js/wayfinder/` | the Vite plugin, on `npm run dev` and `npm run build` |
| `public/build/` | `npm run build` |

If you need them immediately:

```bash
php artisan wayfinder:generate --with-form
```

Route URLs are imported from `@/routes/…` or `@/actions/…`. **Never hard-code an
app URL** and never add React Router — the Laravel route table is the only
routing in this project, and `tests/Unit/ArchitectureTest.php` does not check
that one, so it is on you. (`href="/"` in the parish navbar was removed for
exactly this reason.)

---

## 8. Known local friction

**`upload_max_filesize` is 2M on this machine, and PRD POST-04 asks for 3 MB.**
PHP rejects the request before Laravel sees it, so the application-level
validation of 3 MB is correct and the host is short of it. `PHP_INI_PERDIR`
cannot be changed from `.env`. Raise it in your `php.ini` before testing media
uploads.

**Five `npm audit` criticals are pre-existing.** They are `shell-quote@1.9.0`,
pulled in by `concurrently` from the starter kit. Nothing in this project added
them, and `npm audit fix --force` would try to replace the package manager.

**Head tags live in two places.** With SSR off (D-26), `<Head>` in React never
reaches a crawler, so Open Graph and Twitter Card tags are also rendered in
`resources/views/app.blade.php`. The two layers are joined by a `data-inertia`
attribute, and the key strings must match `head-key` in
`resources/js/components/seo.tsx`. **A new head key has to be added in both
places**, or one of them is ignored.

**`config()` is cached in tests.** If you change a value in `config/` and a test
disagrees with it, `php artisan config:clear` is already the first thing
`composer test` does.

---

## 9. Troubleshooting

| Symptom | Cause |
| --- | --- |
| `could not find driver` | `pdo_mysql` is not enabled |
| Tests fail immediately with a connection error | MySQL is not running, or `website_paroki_hspmtb_test` was never created |
| `npm run dev` warns about a missing manifest | You have never run `npm run build`, and the dev server is not running either |
| E2E fails on every navigation with a 500 | `npm run build` has not been run, so Blade has no asset manifest |
| E2E says a credentials file is missing | Run `npm run e2e`, not `npx playwright test` |
| A test passes alone and fails in the suite | Usually the Spatie permission cache, or a rate limiter; see §6.1 and §6.3 |
| A commit is rejected for no visible reason | Run `npm run check` — `denyWarnings: true` turns a lint warning into a failure |

---

## 10. Where to look next

| Question | File |
| --- | --- |
| Where does this code go? | `ARCHITECTURE.md` |
| Why was it done this way? | `docs/DECISIONS.md` |
| What is the plan, and what is finished? | `docs/roadmap/phase-01-project-foundation.md` |
| How are roles and permissions arranged? | `RBAC.md` |
| What are the design tokens? | `docs/DESIGN.md` |
| What does the product require? | `docs/PRD.md` |
