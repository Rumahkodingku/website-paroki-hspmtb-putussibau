# RBAC

How roles, permissions and authorization work in this application, and the two
places where the obvious-looking code is wrong.

For *where to put a policy* and *what a controller may do*, read
`ARCHITECTURE.md` Part A. For *why a decision was taken*, read the `D-` notes it
cites. This file is about the mechanism.

---

## 1. What exists

One role and fourteen permissions. That is not an oversight: the PRD puts role
management out of MVP scope, and a permission for a module with no code to guard
would be a name nothing reads.

| Group | Permissions |
| --- | --- |
| Entry | `admin.access` |
| Dashboard | `dashboard.view` |
| Settings | `settings.view`, `settings.update` |
| Media | `media.view`, `media.create`, `media.delete` |
| Users | `users.view`, `users.create`, `users.update`, `users.disable` |
| Posts | `posts.view`, `posts.create`, `posts.update`, `posts.delete` |

The role:

| Role | How it gets its permissions |
| --- | --- |
| `super_admin` | **Not attached.** Granted by `Gate::before`. |

The list lives in one constant, `PermissionSeeder::PERMISSIONS`. The role name
is `AppServiceProvider::SUPER_ADMIN`, referenced rather than typed out, because a
role name in a string literal is a typo waiting to silently deny access.

---

## 2. Two ways to get a role, and one of them bypasses the grant

This is the part that produces a security bug if you do not know it.

```php
// AppServiceProvider::configureAuthorization()
Gate::before(function (User $user, string $ability): ?bool {
    return $user->hasRole(self::SUPER_ADMIN) ?: null;
});
```

**`Gate::before` only affects Gate-level checks.** That is `can()`,
`Gate::authorize()`, `can:` middleware, and policy methods. It does **not**
affect a direct call to `hasPermissionTo()`:

```php
Gate::authorize('settings.update');   // granted by Gate::before
$user->hasPermissionTo('settings.update');  // NOT granted — nothing is attached to the role
```

Both are `false` for the same user at the same moment, and neither is a bug.
The second is simply outside the gate.

> **So authorization code must use `can()`.** Never check `hasPermissionTo()` in
> a policy, a controller or a Blade conditional, and you will not notice when
> somebody reads it as the more direct-looking call.

The `posts.*` permissions above are the live example: they are seeded, and
nothing reads them yet.

### 2.1 It must return `null`, never `false`

`Gate::before` runs before every policy. Returning `true` for a super admin is
intended. Returning **`false`** would short-circuit *every* policy in the
application, including for users who are not super admins.

`?bool` in the signature and the `?: null` are both load-bearing. There is a test
named for this: *"denies a user without the super admin role"*.

### 2.2 Why not attach the permissions to the role

Attaching `admin.access`, `settings.*` and the rest to the `super_admin` role
would work today, and would then be a trap: a permission added in Phase 02 would
have to be added in two places, and the one that is forgotten fails silently as a
403 that reads like a bug. `Gate::before` means the grant is a fact rather than a
list.

---

## 3. Where authorization happens

**Once per route.** Never twice, and never only on the frontend.

| Route has | Authorize with | Example |
| --- | --- | --- |
| A FormRequest (`store`, `update`) | `FormRequest::authorize()` and nowhere else | `UpdateSettingsRequest` |
| Anything else | `Gate::authorize()` or a policy | `SettingsController::edit` |
| The whole admin area | `can:` middleware | `EnsureAdminAccess` |

`ARCHITECTURE.md` Part A section 8 states the same rule. The reason it is stated
twice is that `FormRequest::authorize()` running *and* a `Gate::authorize()` in
the controller means two places to keep in step, and one of them is easy to
delete.

### 3.1 Middleware is not a substitute

```php
// routes/web.php
Route::middleware(['auth', 'verified', EnsureAccountIsActive::class, EnsureAdminAccess::class])
```

`EnsureAdminAccess` calls `Gate::authorize('admin.access')`. It answers "may this
person be in the admin area at all" — it does not answer "may this person change
this setting". Each module still authorizes its own writes.

---

## 4. Adding a permission

1. Add the name to `PermissionSeeder::PERMISSIONS`.
2. Run `php artisan db:seed --class=PermissionSeeder`.
3. Authorize with it: `Gate::authorize('your.ability')` in a controller, or in
   the relevant `FormRequest::authorize()`.

Steps 2 and 3 are where it goes wrong. A permission with no `Gate::authorize()`
anywhere is a name nothing reads; `PermissionSeeder` has no way to notice that,
which is why the coverage comment in that file explains which endpoints read
each group today.

The list in that test is the real friction, not a bug in it. `PermissionSeeder`
also exposes `expectedCount()` so a test can assert that seeding twice changes
nothing without restating the number; that says nothing about whether a new
permission is guarded anywhere.

---

## 5. Adding a policy

Only when a rule needs the model — "can this user edit *this* post?" rather than
"may this user edit settings?".

```php
final class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->can('posts.update');
    }
}
```

Then check it in the FormRequest for `update`, or with `Gate::authorize()` in the
controller. Not both, and not in the view.

A `can` object on an Inertia payload is **UX only** — it decides whether a button
is drawn, never whether the request is allowed:

```php
'can' => ['update' => $request->user()->can('update', $post)],
```

---

---

## 6. There is no `users.role` column

Roles come from Spatie and only from Spatie. `users` has no `role` column, and
`User` gets its roles from the `HasRoles` trait:

```php
$user->assignRole('super_admin');
$user->hasRole('super_admin');     // roles
$user->can('settings.update');     // abilities, via Gate
```

A column would create a second answer to "what can this user do", and only one of
them would be consulted. The PRD requires the single source.

This is asserted from both directions, because either can be broken quietly: a
test checks the schema for the column, and `tests/Unit/ArchitectureTest.php`
asserts that no model bypasses Eloquent to read one. A `$user->role` that
happened to be `null` would look identical to "has no roles" at runtime.

---

## 7. Adding roles later

Role management is out of MVP scope, and the PRD says so. When it is built, the
convention this file assumes is the one to follow:

1. **Add the role in `PermissionSeeder`,** alongside the permissions it holds.
   Do not create roles from a controller or from a seeder that runs on boot.
2. **Permissions are grouped by `resource.action`,** matching the existing
   fourteen: `posts.update`, not `update_post` or `editPost`. No test checks that
   *shape* — `RbacFoundationTest` asserts the exact list of fourteen names, so a
   new permission fails the build until that list is updated. That is deliberate
   friction: the convention is enforced by a list somebody has to read, not by a
   regex somebody can satisfy without noticing.
3. **A new role gets permissions attached to it.** That is the whole difference
   from `super_admin`, and it is why `Gate::before` exists — it is the one role
   whose permissions are not attached anywhere.
4. **A rule that depends on the model needs a Policy,** not a role check. "Can
   edit this post" is a Policy; "can edit posts at all" is a permission.
5. **Never check `hasPermissionTo()` in application code** (§2). It reads as the
   more direct call and silently disagrees with `can()`.

Worth being clear about the limit of point 2: a permission named `updatePost`
would pass every existing test as soon as the list was updated to include it.
The convention holds because somebody reads the list when they add to it.

## 8. Deactivating an account

`is_active` is checked in **two** places: the login pipeline and
`EnsureAccountIsActive` middleware.

The middleware alone is not enough. It runs after a session exists, and the point
of `is_active` is that a disabled account cannot obtain one. Deactivating somebody
therefore ends their current session on their next request *and* prevents a new
one.

`is_active` is deliberately **not** in `$fillable` (D-13), because mass assignment
silently dropping it would deactivate an account instead of activating it.
Seeder and admin code uses `forceFill()` for that reason; ordinary request input
must never touch it.

---

## 9. Tests

Most tests need the role and its permissions:

```php
seedRolesAndPermissions();   // from tests/Pest.php
```

It seeds them **and drops Spatie's permission cache**. Skipping it is the usual
reason a test passes alone and fails in a suite — `CACHE_STORE=array` does not
survive between tests, so the cache has to be cleared by hand.

What is asserted today, and where:

| Behaviour | Test |
| --- | --- |
| `super_admin` passes a policy through `Gate::before` | `tests/Feature/Foundation/RbacFoundationTest.php` |
| A user without the role is denied — i.e. `Gate::before` returns `null` | same file |
| `users` has no `role` column | same file, against the database |
| Roles come from Spatie, not a column | `tests/Unit/ArchitectureTest.php` |
| A guest cannot reach the admin area | `tests/Feature/Auth/AdminAccessTest.php` |
| A signed-in non-super-admin is refused | same file |
| A deactivated account cannot sign in | `tests/Feature/Auth/AdminAccessTest.php` |
| A deactivated session is thrown out of the admin area | same file |
| The inactive error message is in Indonesian | same file |

When you add a policy, add the refusal case, not only the allow case. A 403 with
no test is the failure everyone notices first.

---

## 10. Quick reference

| Want to check | Use | Not |
| --- | --- | --- |
| May this user do X? | `$user->can('x')` | `$user->hasPermissionTo('x')` |
| Stop unless allowed | `Gate::authorize('x')` | `abort_unless(...)` |
| Hide a button | `can` in the payload, or `$user->can()` | trusting the absence of a button |
| Make a super admin | give them the `super_admin` role | attaching every permission |
| Make a normal role | attach permissions to the role | — |

```php
// granted by Gate::before
Gate::authorize('settings.update');

// false for the same user, because it does not go through the gate
$user->hasPermissionTo('settings.update');
```
