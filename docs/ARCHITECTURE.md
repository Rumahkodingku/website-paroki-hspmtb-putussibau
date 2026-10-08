# Architecture

> **Standard architecture for Laravel 13 + Inertia + React 19 applications.**
>
> This copy is the one the project works by. Where the project chose something
> different from the pattern the document originally described, the document
> describes the project rather than the other way round, and says why.
>
> Every `⚠ CONFIRM` marker has been resolved against `composer.json`,
> `package.json` and `vite.config.ts`, with the resolution recorded inline where
> the assumption used to be.

---

## Table of Contents

- [Part 0 — Overview](#part-0--overview)
- [Part A — Backend (Laravel 13)](#part-a--backend-laravel-13)
- [Part B — Frontend (React 19 + Inertia + TypeScript)](#part-b--frontend-react-19--inertia--typescript)
- [Part C — Inertia Data Contract (Laravel → React)](#part-c--inertia-data-contract-laravel--react)
- [Part D — Testing](#part-d--testing)
- [Part E — Reference Example: Product CRUD (end-to-end)](#part-e--reference-example-product-crud-end-to-end)

---

## Part 0 — Overview

This project uses a **standard layered Laravel architecture**, a
**feature-oriented React frontend**, and **Inertia** as the bridge.
Laravel remains recognizable as Laravel; React stays focused on
presentation. Add structure only when complexity requires it.

```text
Browser (React 19)  →  Inertia  →  Laravel 13  →  Eloquent  →  Database
```

Backend request flow (middleware and FormRequest run BEFORE the controller body):

```text
Route → Middleware → FormRequest (authorize + validate)
      → Controller method → [Action / Service] → Eloquent → Inertia response / redirect
```

### Core principles

1. Laravel is the backend and the source of truth for data, auth, validation, and business logic.
2. Inertia connects Laravel and React. Laravel routes are the only application routing.
3. React handles presentation and client interaction only.
4. Controllers stay thin; business operations move to Actions or Services when thresholds in Part A are met.
5. Eloquent is the default persistence layer. No repositories by default.
6. Server state is not duplicated into client state.
7. Authorization is always enforced on the backend.
8. Every abstraction must solve a real, present problem.
9. Follow existing project conventions; avoid unrelated refactoring.

### Where does this code go?

| Problem                                                            | Location                                                |
| ------------------------------------------------------------------ | ------------------------------------------------------- |
| HTTP input validation                                              | Form Request                                            |
| Authorization                                                      | Policy / Gate                                           |
| HTTP orchestration                                                 | Controller                                              |
| Plain CRUD (one model, no side effects)                            | Controller + Eloquent                                   |
| Business operation (multi-step, transaction, side effects, reused) | Action                                                  |
| Reusable capability / external integration                         | Service (named by capability)                           |
| Simple query                                                       | Eloquent                                                |
| Query reused or complex                                            | Model scope (Query class only if a scope is not enough) |
| Async work                                                         | Job                                                     |
| Occurrence several parts react to                                  | Event + Listener                                        |
| User notification                                                  | Notification                                            |
| Inertia page                                                       | `resources/js/pages`                                    |
| Feature-specific UI                                                | `resources/js/features`                                 |
| Reusable UI                                                        | `resources/js/components`                               |
| Generic React behavior                                             | `resources/js/hooks`                                    |
| Browser-local state                                                | React state                                             |
| Shareable filters                                                  | URL query string                                        |
| Server state                                                       | Laravel + Inertia props                                 |
| Application routing                                                | Laravel routes                                          |

### Anti-patterns

- God controller / god component.
- A Service or Repository for every model.
- Copying Inertia props into global state.
- React Router, or an internal API, for data an Inertia page can already receive.
- Frontend-only authorization.
- Premature abstraction and unrelated refactoring.

---

## Part A — Backend (Laravel 13)

### 1. Directory structure Backend

```text
app/
├── Actions/          # one class per business operation
├── Enums/            # backed enums (status, type, role)
├── Events/  Listeners/  Jobs/  Notifications/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/     # FormRequests
│   └── Resources/    # API Resources used to shape Inertia props
├── Models/
├── Policies/
├── Providers/
├── Services/         # reusable capabilities (named by capability)
└── Support/          # small helpers only
```

Create a directory only when you have a first file to put in it. Never
create a class just to "fill" the structure.

### 2. Controllers

A controller method MAY: authorize, read validated input, query data, call
one Action/Service, return `Inertia::render()` or a redirect.

A controller method MUST NOT: contain multi-step business workflows, pricing
or status rules, external API calls, notifications, or manual transactions.

Extract an Action when ANY of these is true:

- the method exceeds ~15 lines;
- it writes to more than one model;
- it needs a transaction;
- it dispatches an event, job, or notification;
- the same operation is called from 2 or more entry points (controller, job, command).

Plain CRUD (one model, no side effects, ≤ ~15 lines) stays in the controller
with Eloquent. Do not add an Action for it.

Use resource methods: `index create store show edit update destroy`. Custom
operations get their own invokable controller or a clearly named method
(`publish`, `archive`), never a generic `handle`/`process`.

After a mutation: redirect (`to_route(...)`), never render a page directly
from a POST/PUT/DELETE.

### 3. FormRequests

- All writes use a FormRequest. No inline `$request->validate()` in
  controllers except trivial one-field cases.
- `authorize()` is the single place for `store`/`update` authorization
  (see Authorization in this part).
- Use `Rule::enum()` for enums, `Rule::unique()->ignore()` on update.
- Rules only. No business logic, no DB writes.

```php
final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'], // minor units
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }
}
```

### 4. Actions

An Action is a plain class with a `handle()` **instance** method.
Dependencies come through the constructor. The controller receives the
Action via method injection. No `static` methods.

```php
final class PublishProductAction
{
    public function __construct(private readonly SearchIndexService $index) {}

    public function handle(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $product->update(['status' => ProductStatus::Published]);

            ProductPublished::dispatch($product);
            $this->index->sync($product);

            return $product;
        });
    }
}
```

- One public method: `handle()`.
- Name = verb + noun + `Action`: `CreateOrderAction`, `CancelOrderAction`.
- Takes validated arrays or models/primitives. Never the `Request` object.
- Owns the transaction for its operation.
- Do NOT create an Action for a one-line CRUD write.

### 5. Services

Named by capability: `PaymentService`, `FileStorageService`,
`CurrencyService`. Never `ProductService` / `UserService` / `OrderService`.
Services hold reusable logic or integrations; they do not read the HTTP
request.

### 6. Eloquent

- Eloquent is the persistence layer. No repositories.
- Enable strict mode in `AppServiceProvider::boot()` (non-production or all
  environments per team decision): `Model::shouldBeStrict()` (prevents lazy
  loading, silently discarded attributes, missing attributes).
- Always declare `$fillable` explicitly. Casts via `casts()`.
- Status/type fields cast to backed enums.
- Put relationships, scopes, casts, simple accessors in models. Put
  workflows in Actions.
- Eager load: `with()` for every relation the page renders. Paginate
  anything that can grow.
- Select only needed columns for large lists.

```php
protected function casts(): array
{
    return ['status' => ProductStatus::class];
}
```

### 7. Transactions, events, jobs

- Wrap a business operation (not random queries) in `DB::transaction()`.
- Jobs/events dispatched inside a transaction MUST be dispatched after
  commit: implement `ShouldDispatchAfterCommit` on the event/job or use
  `->afterCommit()`, so workers never see uncommitted data.
- Jobs MUST be idempotent where practical (safe to retry). Set `tries`
  and `backoff` for external calls.
- Use Events when ≥ 2 listeners react to one occurrence. Do not use events
  to hide an ordinary method call.

### 8. Authorization

- One Policy per model that needs rules: `ProductPolicy`.
- **Single-place rule:** for routes that have a FormRequest (`store`,
  `update`), authorize in `FormRequest::authorize()` only. For everything
  else (`index`, `show`, `edit`, `destroy`, custom actions) use
  `Gate::authorize('ability', $model)` in the controller or `can:` route
  middleware. Never authorize the same action in both places.
- Do not rely on `$this->authorize()` unless the base `Controller` uses the
  `AuthorizesRequests` trait. Check `app/Http/Controllers/Controller.php`.
- Register/discover per Laravel 13 defaults; do not hand-roll gates for
  model-based rules.
- Routes for resources SHOULD also be guarded by `auth` (and `verified` if
  the app requires it) middleware groups in `routes/web.php`.
- Never trust the frontend to hide or disable something as security.

### 9. Routing

- `routes/web.php` for all Inertia pages and form mutations.
- `routes/api.php` only for a real external API requirement (mobile app,
  third parties, public API). Do not create an internal API to feed a page.
- Use `Route::resource()` / `Route::middleware()->group()`. Name routes
  (`products.index`). After changing routes or controllers, regenerate
  Wayfinder: `php artisan wayfinder:generate --with-form`.

### 10. Errors

- Validation → FormRequest → Inertia errors (automatic).
- Forbidden → Policy → 403. Missing → route model binding → 404.
- Never `catch (Throwable $e) { return null; }`. Catch only when you can
  handle the failure; otherwise let the exception handler log it.
- For external calls: catch the specific exception, log with context,
  surface a user-friendly message (flash/validation error).

### 11. Security checklist

Validate every input · authorize every protected operation · CSRF stays on ·
explicit `$fillable` · never send secrets or sensitive columns to React ·
validate file uploads (type, size) · rate-limit sensitive endpoints (login,
OTP, export) · secrets only in `.env` via `config()`.

### 12. Caching

Cache only with a measured reason. Always define key, TTL, invalidation, and
consistency expectation in a comment next to the cache call. Do not cache
without an invalidation plan.

### 13. Database

- Every schema change is a new migration. Never edit a run migration.
- Every model gets a factory. Seeders only for reference data/dev data.
- Add indexes for foreign keys, filter, and sort columns used by lists.
- Money: integer minor units. Do not use float/double.

---

## Part B — Frontend (React 19 + Inertia + TypeScript)

### 1. Directory structure Frontend

```text
resources/js/
├── actions/          # GENERATED by Wayfinder. Do not edit
├── routes/           # GENERATED by Wayfinder. Do not edit
├── wayfinder/        # GENERATED by Wayfinder. Do not edit
├── components/       # app-level UI used from more than one place
│   ├── ui/           # shadcn primitives. Do not edit
│   └── shared/       # not created yet — see below
├── features/         # feature-specific frontend code
│   └── products/
│       ├── components/   # product-form.tsx, product-table.tsx ...
│       ├── hooks/        # use-product-filters.ts
│       ├── types.ts
│       └── utils.ts
├── hooks/            # generic hooks (use-debounce.ts)
├── layouts/          # app-layout.tsx, auth-layout.tsx ...
├── lib/              # utilities (cn(), formatters)
├── pages/            # Inertia entry points
│   └── products/ index.tsx create.tsx edit.tsx show.tsx
├── types/            # site-wide types (SharedProps, Paginated<T>, Auth)
└── app.tsx           # Inertia bootstrap
```

`features/` is frontend-only grouping. It has nothing to do with a PHP
`app/Modules` directory (which does not exist and must not be created).

**The line between `components/` and `features/<name>/` is how many places use
it.** One consumer means it belongs to that feature; two or more makes it
app-level. The cases in this repository:

| Component                     | Consumers                     | Lives in                        |
| ----------------------------- | ----------------------------- | ------------------------------- |
| `appearance-tabs.tsx`         | the appearance page only      | `features/akun/components/`     |
| `rich-text-editor.tsx`        | the settings page only        | `features/pengaturan/components/` |
| settings types                | the settings page only        | `features/pengaturan/types.ts`  |
| `password-input.tsx`          | four auth and account pages   | `components/`                   |
| `heading.tsx`                 | five pages                    | `components/`                   |
| `text-link.tsx`               | three auth pages              | `components/`                   |

**`components/shared/` is not created yet.** It only means something once two or
more features exist, and Part A says a directory is created when it has a first
file. `types/` holds only the types more than one feature needs, so that deleting
a feature deletes its contract along with it.

These boundaries are asserted in `tests/Unit/ArchitectureTest.php`. The document
is not the only thing keeping them in place.

### 2. Naming

| Thing              | Convention                       | Example                                   |
| ------------------ | -------------------------------- | ----------------------------------------- |
| File               | kebab-case                       | `product-status-badge.tsx`                |
| Component export   | PascalCase                       | `ProductStatusBadge`                      |
| Hook file / export | `use-*.ts` / `useX`              | `use-debounce.ts` / `useDebounce`         |
| Page file          | matches `Inertia::render()` name | `products/index.tsx` ↔ `'products/index'` |
| Feature folder     | lowercase plural                 | `features/products`                       |

shadcn files are already kebab-case. Follow the same rule everywhere.

### 3. Pages

A page: receives typed props, composes feature components, and coordinates
page-level interaction. A page is thin. Do not put large reusable UI inside
`pages/`.

```tsx
import { ProductTable } from "@/features/products/components/product-table";
import type { Paginated } from "@/types";
import type { ProductListItem } from "@/features/products/types";

type Props = { products: Paginated<ProductListItem> };

export default function Index({ products }: Props) {
    return <ProductTable products={products} />;
}

Index.layout = {
    breadcrumbs: [{ title: "Products", href: index() }],
};
```

**Deviation — layouts are resolved centrally, not wrapped per page.** The
example above shows a page importing a layout and wrapping itself. This project
does not do that. `resources/js/app.tsx` passes a `layout` callback to
`createInertiaApp`, which picks a layout from the page component's name, and a
page supplies its own layout *props* through the static `Component.layout`
field:

```ts
// resources/js/app.tsx
layout: (name) => {
    switch (true) {
        case name === "welcome": return PublicLayout;
        case name.startsWith("public/auth/"): return AuthLayout;
        case name.startsWith("admin/akun/"): return [AppLayout, SettingsLayout];
        default: return AppLayout;
    }
},
```

The reason is Inertia's persistent layout: the layout stays mounted across
navigation, so the admin sidebar does not tear down and rebuild on every visit
to a different page. Wrapping inside each page would remount it every time.

Keep the callback in `app.tsx` and the props on the page. A page therefore does
not import a layout at all, which is why the example above has no layout
wrapper. Adding one would nest a second layout inside the persistent one.

### 4. Dependency direction

```text
pages → features → components / hooks / lib / types
```

- `features/a` MUST NOT import from `features/b`. If both need it, move it to
  `components/shared`, `hooks`, `lib`, or `types`.
- `components/ui` and `components/shared` MUST NOT import from `features/*`
  or `pages/*`.
- Components never touch backend concepts (Eloquent, PHP classes).

### 5. Routes and navigation (Wayfinder)

**Confirmed: Wayfinder**, not Ziggy. `laravel/wayfinder` generates
`resources/js/actions/` and `resources/js/routes/`, both gitignored. The Vite
plugin regenerates them on `npm run dev` and `npm run build`, so a route change
is picked up without running anything by hand; `php artisan wayfinder:generate
--with-form` is for when you want the files now.

- Import controller actions or named routes from the generated modules:

```tsx
import { index, store } from "@/actions/App/Http/Controllers/ProductController";
import { Link } from "@inertiajs/react";

<Link href={index()}>Products</Link>;
form.submit(store()); // Wayfinder object accepted by Inertia
```

- Never hard-code URL strings for app routes. Never use React Router.
- Re-run `php artisan wayfinder:generate --with-form` after backend route
  or controller changes.

### 6. State

| State                                             | Where                                                              |
| ------------------------------------------------- | ------------------------------------------------------------------ |
| Server data (records, permissions, notifications) | Inertia props. Never copy into a store                             |
| Form data                                         | Inertia `useForm`                                                  |
| Local UI (open/closed, active tab)                | `useState` in the component                                        |
| Cross-component UI (sidebar, command palette)     | Context, or the global state library **only if already installed** |
| Shareable (filters, sort, search, page)           | URL query string via Inertia `router.get`                          |

Do not mirror props into `useState` just to "use them". Derive values during
render. Use `useEffect` only to sync with external systems, never to
compute derived data.

### 7. Forms

- Use Inertia `useForm` (or Inertia's `<Form>` component if the project
  uses it). Backend validation is authoritative; show `form.errors` next to
  the matching field.
- Mutations MUST go through Inertia. Do NOT use React 19 `<form action>`,
  `useActionState`, or `useOptimistic` for server writes.
- Disable the submit button with `form.processing`. Show a success state
  from flash messages.
- Client-side validation is optional UX only and must not duplicate
  backend rules beyond simple required/format hints.

```tsx
const form = useForm({ name: "", price: 0 }); // price in minor units

function submit(e: React.FormEvent) {
    e.preventDefault();
    form.submit(store());
}
```

### 8. Data fetching

- Page data: Inertia props. Heavy or optional data: Inertia deferred /
  partial reloads (`only`), not `useEffect` + fetch.
- Direct `fetch` is acceptable only for: external APIs, browser-only
  interactions (e.g. autocomplete endpoint that exists for that purpose),
  or a genuine API-driven widget.
- Do not add axios/SWR/React Query unless already in `package.json`.

### 9. TypeScript

- No `any` without a comment. Prefer `unknown` + narrowing.
- Every page has a `Props` type that mirrors the PHP payload
  (see Part C).
- Shared generic types live in `types/` (`Paginated<T>`, `SharedProps`,
  `Auth`). Site-wide only: a type one feature needs lives in that feature.
- Feature types live in `features/<name>/types.ts`.
- **Deviation — there is no `PageProps` type.** `types/global.d.ts` augments
  Inertia's `InertiaConfig` with
  `sharedPageProps: SharedProps & Record<string, unknown>`, so every page
  already receives one merged props type from the framework. A local `PageProps`
  would be a second name for it.
- Money fields are `number` in minor units; format only at display time
  with a `lib/` formatter.

### 10. UI quality

Every data-driven view handles: loading (when deferred), empty, error, and
validation-error states. Use semantic HTML, labels bound to inputs, visible
focus, keyboard operability, and responsive layouts (mobile first).
Use shadcn components and existing tokens. Do not add a new UI library or
hard-code colors that bypass the theme tokens.

### 11. React 19 notes for this project

- Prefer plain function components and hooks. Do not add `memo`/`useMemo`/
  `useCallback` by default. Add only after a measured problem.
- **Confirmed: the React Compiler is enabled.** `vite.config.ts` passes
  `babel({ presets: [reactCompilerPreset()] })`, which means memoisation is
  handled for us and a hand-written `memo` or `useCallback` is redundant work.
  New code does not add them.
- Ref as a prop is allowed (no `forwardRef` needed in new code).
- Follow existing code if it still uses older patterns. Do not migrate
  unrelated code.

---

## Part C — Inertia Data Contract (Laravel → React)

### 1. Golden rules

1. MUST NOT pass raw Eloquent models or unshaped collections to
   `Inertia::render()`. Everything sent to the browser is visible in the
   page source.
2. Send only the fields the page renders.
3. Every Inertia page has a matching TypeScript `Props` type.
4. If you change the PHP payload, you MUST update the TS type in the same
   change.
5. Shared props stay small and intentional.

### 2. Shaping data: API Resources

Use `JsonResource` classes in `app/Http/Resources` as the single mapping
point. Keep a list-item shape and a detail shape separate when they differ.

```php
final class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,              // integer minor units
            'status' => $this->status->value,     // enum -> string
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'can' => [
                'update' => $request->user()->can('update', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
            ],
        ];
    }
}
```

Controller:

```php
return Inertia::render('products/index', [
    'products' => ProductListResource::collection(
        Product::query()->with('category')->latest()->paginate(15)->withQueryString()
    ),
    'filters' => $request->only(['search', 'status']),
]);
```

Notes:

- `JsonResource::collection($paginator)` yields `data`, `links`, `meta`.
  Keep that shape consistent app-wide (see section 4 of this part).
- Never include `password`, tokens, internal flags, or other users' private
  data. Use `$hidden` as a safety net, not as the contract.
- Enum → send `->value` (or the label if the UI needs it).
- Dates → ISO 8601 strings. Format for display in React.
- Per-row permission booleans go in a `can` object. React uses them for UX
  only. The backend still enforces on every request.

### 3. Matching TypeScript

```ts
// features/products/types.ts
export type ProductStatus = "draft" | "published" | "archived";

export type ProductListItem = {
    id: number;
    name: string;
    price: number; // minor units
    status: ProductStatus;
    category?: { id: number; name: string };
    can: { update: boolean; delete: boolean };
};
```

`status` unions MUST mirror the PHP enum cases. When an enum changes, update
both sides.

### 4. Standard shapes (`resources/js/types/index.ts`)

**Confirmed.** `Paginated<T>` is in `resources/js/types/pagination.ts` and is
Laravel's own shape — what `JsonResource::collection($paginator)` serialises to.
`SharedProps` is in `resources/js/types/index.ts` and mirrors what
`HandleInertiaRequests::share()` sends; the auth shape beside it is closed, with
no index signature, so reading a field the backend does not send is a type error
rather than a runtime `undefined`.

```ts
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
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
};

export type Auth = {
    user: { id: number; name: string; email: string } | null;
};

export type SharedProps = {
    auth: Auth;
};
```

**Deviation — `flash` is not a shared prop.** Flash messages are delivered on a
`flash` DOM event rather than as a page prop, so `SharedProps` has no `flash`
key and `types/index.ts` says why. A page that needs flash reads it from the
event, not from its props.

### 5. Shared props (`HandleInertiaRequests::share`)

Allowed: authenticated user (id, name, email, minimal role/permission
summary), flash messages, app name/locale config.

Forbidden: record lists, full permission tables, anything large or private.

Use closures (lazy) for anything non-trivial:

```php
'auth' => ['user' => fn () => $request->user()?->only('id', 'name', 'email')],
'flash' => fn () => [
    'success' => $request->session()->get('success'),
    'error' => $request->session()->get('error'),
],
```

### 6. Flash messages

Set with `->with('success', '...')` on redirect.

**Deviation** — read from the `flash` DOM event, not from shared props. The
alternative in the original text is a `flash` key on every page's props, which
would put a string nobody rendered on every page. Two consequences: the toast
handler lives in one place (`hooks/use-flash-toast.ts`), and success messages
still never pass through page props.

### 7. Partial, deferred, and optional data

For expensive or secondary data use Inertia's lazy mechanisms
(`Inertia::defer()`, `Inertia::optional()`, partial reloads with `only`)
instead of a client fetch.

**Confirmed for Inertia 3**, which is what `createInertiaApp` here is built
against: deferred props via `Inertia::defer()`, optional props via
`Inertia::optional()`, partial reloads via `router.get(url, {}, { only: [...] })`,
and `prefetch` on `Link`. The renamed events are `httpException` (was `invalid`)
and `networkError` (was `exception`).

### 8. Validation errors

Laravel validation errors are returned automatically and appear in
`form.errors`. Do not build a custom error envelope. Error messages are
backend-owned and localized on the backend.

### 9. Pre-merge contract checklist

- [ ] No raw model/collection sent to `Inertia::render`
- [ ] No hidden/sensitive field exposed
- [ ] Resource fields ⇄ TS type match
- [ ] Enum cases ⇄ TS union match
- [ ] Money sent as integer minor units
- [ ] Paginated lists use `->withQueryString()`
- [ ] Relations used by the resource are eager loaded

---

## Part D — Testing

Test runner: **Pest** (4.7.8, with the Laravel plugin). Pest syntax
throughout; PHPUnit syntax is not mixed in.

### 1. Rules

- Behavior changes MUST come with tests in the same change.
- Prefer Feature tests that exercise the real HTTP flow. Add Unit tests only
  for Actions/Services with real logic and for pure utilities.
- Use factories. Never rely on seeded data inside tests.
- Do not mock Eloquent or the database. Mock only external services
  (HTTP, payment, storage) with Laravel fakes (`Http::fake`, `Queue::fake`,
  `Event::fake`, `Notification::fake`, `Storage::fake`).
- A test names the behavior: `it('rejects a guest from creating a product')`.

### 2. Minimum coverage for a new endpoint

Every new route that reads or writes data needs tests for:

1. **Success path**: correct redirect or Inertia component.
2. **Validation failure**: invalid input returns errors, nothing persisted.
3. **Authorization**: guest is redirected/blocked; authenticated but
   unauthorized user gets 403.
4. **Side effects** (if any): job dispatched, event fired, notification
   sent.

### 3. Asserting Inertia responses

```php
use Inertia\Testing\AssertableInertia as Assert;

it('lists products for an authorized user', function () {
    $user = User::factory()->create();
    Product::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
            ->has('products.data', 3)
            ->has('products.data.0', fn (Assert $row) => $row
                ->hasAll(['id', 'name', 'price', 'status', 'can'])
                ->missing('internal_notes'))
        );
});
```

Also assert that sensitive fields are NOT present (`->missing(...)`).

### 4. Write-path example

```php
it('creates a product', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Coffee', 'price' => 15000, 'status' => 'draft',
        ])
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', ['name' => 'Coffee', 'price' => 15000]);
});

it('rejects invalid product data', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('products.store'), ['name' => '', 'price' => -1])
        ->assertSessionHasErrors(['name', 'price', 'status']);

    $this->assertDatabaseCount('products', 0);
});
```

(`route()` in PHP tests is Laravel's helper and is fine. The Wayfinder rule
applies to the React side only.)

### 5. Actions and Services

Unit-test with real dependencies when cheap; fake external boundaries.
Assert the transaction outcome: all-or-nothing behavior, events/jobs
dispatched after commit.

### 6. Frontend tests

**Confirmed: there is a frontend test runner.** Two suites, and they are not
interchangeable:

| Suite | Runner | Location | Runs on |
| --- | --- | --- | --- |
| Unit / component | Vitest via `vp test`, happy-dom | `tests/js/` | every commit, via `composer ci:check` |
| End-to-end | Playwright, Chromium | `tests/e2e/` | the `e2e` CI job, and by hand |

Both live under `tests/`, beside the Pest suites. Vitest cannot collect the
Playwright specs — its own default glob is `**/*.spec.*` — so `tests/e2e/**` is
excluded explicitly.

Neither suite goes in `pre-commit`: starting a web server, rebuilding a database
and downloading a browser turns a one-line change into minutes. That is the `e2e`
job's job, not the code gate's.

### 7. Before reporting done

Run the affected tests, then the full suite if the change touches shared
code (middleware, base classes, shared props, enums, policies). Report
exact commands and results.

---

## Part E — Reference Example: Product CRUD (end-to-end)

Copy this pattern for standard CRUD features. It shows the **simple path**
(no Action for plain CRUD) and one **Action** for a real business operation
(`publish`). Adapt names; keep the structure.

**Confirmed.** Wayfinder import paths are as written: named routes come from
`@/routes/<name>` and controller actions from `@/actions/App/Http/Controllers/…`.
Pages pass layout *props* through the static `Component.layout` field, and pick
the layout component themselves nowhere — `app.tsx` does that from the component
name. See Part B section 3.

### File map

```text
database/migrations/xxxx_create_products_table.php
database/factories/ProductFactory.php
app/Enums/ProductStatus.php
app/Models/Product.php
app/Policies/ProductPolicy.php
app/Http/Requests/StoreProductRequest.php
app/Http/Requests/UpdateProductRequest.php
app/Http/Resources/ProductListResource.php
app/Http/Controllers/ProductController.php
app/Http/Controllers/PublishProductController.php
app/Actions/PublishProductAction.php          # only because publish has side effects
routes/web.php
resources/js/pages/products/{index,create,edit}.tsx
resources/js/features/products/components/{product-form,product-table}.tsx
resources/js/features/products/types.ts
tests/Feature/Products/ProductTest.php
```

### 1. Migration, enum, model

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedInteger('price');                 // minor units
    $table->string('status')->default('draft')->index();
    $table->timestamps();
});
```

```php
enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
```

```php
class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'status'];

    protected function casts(): array
    {
        return ['status' => ProductStatus::class];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }
}
```

### 2. Policy

```php
final class ProductPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function create(User $user): bool { return true; }       // adapt
    public function update(User $user, Product $product): bool { return true; }
    public function delete(User $user, Product $product): bool { return true; }
}
```

### 3. FormRequests (authorization lives here for store/update)

```php
final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }
}
```

`StoreProductRequest` is identical except `authorize()` checks
`can('create', Product::class)`.

### 4. Controller: plain CRUD, no Action

```php
final class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Product::class);

        $products = Product::query()
            ->when($request->string('search')->toString(),
                fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('products/index', [
            'products' => ProductListResource::collection($products),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('products/create', [
            'statuses' => array_map(fn ($s) => $s->value, ProductStatus::cases()),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return to_route('products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('update', $product);

        return Inertia::render('products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'status' => $product->status->value,
            ],
            'statuses' => array_map(fn ($s) => $s->value, ProductStatus::cases()),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);
        $product->delete();

        return to_route('products.index')->with('success', 'Product deleted.');
    }
}
```

Note: no Action here. Each method is a one-model write with no side effects.

### 5. Action: only where side effects exist

```php
final class PublishProductAction
{
    public function handle(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $product->update(['status' => ProductStatus::Published]);
            ProductPublished::dispatch($product); // event implements ShouldDispatchAfterCommit

            return $product;
        });
    }
}

final class PublishProductController extends Controller
{
    public function __invoke(Product $product, PublishProductAction $action): RedirectResponse
    {
        Gate::authorize('update', $product);
        $action->handle($product);

        return back()->with('success', 'Product published.');
    }
}
```

### 6. Routes

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class)->except('show');
    Route::post('products/{product}/publish', PublishProductController::class)
        ->name('products.publish');
});
```

Then run `php artisan wayfinder:generate --with-form`.

### 7. Frontend types

```ts
// features/products/types.ts
export type ProductStatus = "draft" | "published" | "archived";

export type ProductListItem = {
    id: number;
    name: string;
    price: number;
    status: ProductStatus;
};

export type ProductFormData = {
    name: string;
    price: number;
    status: ProductStatus;
};
```

### 8. Feature component (form shared by create and edit)

```tsx
// features/products/components/product-form.tsx
import { useForm } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { ProductFormData, ProductStatus } from "../types";

type Props = {
    initial: ProductFormData;
    statuses: ProductStatus[];
    submitLabel: string;
    onSubmit: (form: ReturnType<typeof useForm<ProductFormData>>) => void;
};

export function ProductForm({
    initial,
    statuses,
    submitLabel,
    onSubmit,
}: Props) {
    const form = useForm<ProductFormData>(initial);

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                onSubmit(form);
            }}
            className="space-y-4"
        >
            <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
                <Input
                    id="name"
                    value={form.data.name}
                    onChange={(e) => form.setData("name", e.target.value)}
                />
                {form.errors.name && (
                    <p className="text-sm text-destructive">
                        {form.errors.name}
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="price">Price</Label>
                <Input
                    id="price"
                    type="number"
                    min={0}
                    value={form.data.price}
                    onChange={(e) =>
                        form.setData("price", Number(e.target.value))
                    }
                />
                {form.errors.price && (
                    <p className="text-sm text-destructive">
                        {form.errors.price}
                    </p>
                )}
            </div>

            {/* status: use the project's shadcn Select; omitted for brevity */}

            <Button type="submit" disabled={form.processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
```

### 9. Pages (thin)

```tsx
// pages/products/create.tsx
import { store } from "@/actions/App/Http/Controllers/ProductController";
import { ProductForm } from "@/features/products/components/product-form";
import type { ProductStatus } from "@/features/products/types";

type Props = { statuses: ProductStatus[] };

export default function Create({ statuses }: Props) {
    return (
        <ProductForm
            initial={{ name: "", price: 0, status: "draft" }}
            statuses={statuses}
            submitLabel="Create"
            onSubmit={(form) => form.submit(store())}
        />
    );
}

// No layout import and no wrapper: app.tsx resolves the layout from this
// page's component name, and the page supplies only layout props. See
// Part B section 3.
Create.layout = {
    title: "Create product",
    breadcrumbs: [{ title: "Products", href: index() }],
};
```

`edit.tsx` follows the same shape, using `update({ product: id })` from the
Wayfinder actions and `initial` taken from the `product` prop.

`index.tsx` renders `ProductTable` from `features/products/components`,
reads `products: Paginated<ProductListItem>` and `filters`, and updates the
URL with `router.get(index().url, { search }, { preserveState: true })` for
searching. No local copy of the list.

### 10. Tests

```php
it('creates a product', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('products.store'), ['name' => 'Coffee', 'price' => 15000, 'status' => 'draft'])
        ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', ['name' => 'Coffee']);
});

it('rejects invalid input', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('products.store'), ['name' => '', 'price' => -1])
        ->assertSessionHasErrors(['name', 'price', 'status']);
});

it('blocks guests', function () {
    $this->post(route('products.store'), [])->assertRedirect(route('login'));
});

it('renders the index component', function () {
    Product::factory()->count(3)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('products.index'))
        ->assertInertia(fn ($page) => $page
            ->component('products/index')
            ->has('products.data', 3));
});

it('publishes a product and fires the event', function () {
    Event::fake([ProductPublished::class]);
    $product = Product::factory()->create(['status' => 'draft']);

    $this->actingAs(User::factory()->create())
        ->post(route('products.publish', $product))
        ->assertRedirect();

    expect($product->fresh()->status)->toBe(ProductStatus::Published);
    Event::assertDispatched(ProductPublished::class);
});
```

Add a 403 test for any policy that restricts by role or ownership.

### What to copy from this example

- CRUD without side effects → controller + Eloquent, no Action.
- Business operation with side effects → Action, instance `handle()`, transaction.
- Authorization once per route (FormRequest or `Gate::authorize`).
- Props shaped explicitly; TS types mirror them; filters in the URL.
- Pages thin; forms and tables live in `features/products`.
