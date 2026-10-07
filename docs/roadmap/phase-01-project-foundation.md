# PHASE 01 — PROJECT FOUNDATION

**Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau  
**Phase:** 01 — Project Foundation / Fondasi Proyek  
**PRD Reference:** PRD.md v1.1 (RBAC Foundation — 6 Oktober 2026)  
**Status:** Ready for Implementation  
**Stack:** Laravel 13 + Inertia.js + React 19 + Vite + Tailwind CSS v4 + shadcn/ui + MySQL  
**Architecture:** Standard Laravel Modular Monolith  
**Timezone:** UTC storage / Asia-Pontianak display  
**Audience:** AI coding agents dan developer

---

## 1. Tujuan Phase

Phase 01 membangun seluruh fondasi teknis yang dibutuhkan sebelum modul bisnis Paroki mulai dikembangkan.

Hasil akhir Phase 01 harus membuat repository siap digunakan untuk pengembangan modul berikutnya tanpa perlu melakukan refactor fondasi besar.

Phase ini berfokus pada:

1. baseline project dan environment;
2. konfigurasi Laravel;
3. database dan migration foundation;
4. Docker development environment;
5. authentication;
6. Spatie Laravel Permission sebagai fondasi RBAC;
7. authorization menggunakan Spatie + Policy/Gate;
8. Inertia + React foundation;
9. UI foundation;
10. layout publik dan admin;
11. site settings;
12. media processing;
13. HTML sanitization dan Tiptap;
14. queue, cache, dan scheduler foundation;
15. error handling dan SEO foundation;
16. automated testing dan code quality;
17. Git workflow dan dokumentasi developer.

> **Source of Truth:** PRD.md v1.1 adalah sumber kebenaran utama. Jika dokumen Phase 01 ini berbeda dengan PRD, PRD harus diprioritaskan dan perbedaan harus dicatat di `docs/DECISIONS.md`.

---

# 2. Referensi PRD

Phase ini terutama mengimplementasikan keputusan dan requirement berikut:

| Reference | Area                                              |
| --------- | ------------------------------------------------- |
| D-01      | UTC storage + Asia/Pontianak display              |
| D-08      | `users.is_active`                                 |
| D-14      | Tiptap + server-side HTML sanitization            |
| D-15      | Laravel authentication + no public registration   |
| D-16      | Spatie Laravel Permission sebagai RBAC foundation |
| AUTH-R1   | Admin route protection                            |
| AUTH-R2   | No public registration                            |
| AUTH-R3   | Spatie `HasRoles`, tanpa `users.role`             |
| AUTH-R4   | Policy/Gate untuk contextual authorization        |
| AUTH-R5   | `super_admin` sebagai system role                 |
| AUTH-R6   | Action-resource permission naming                 |
| XC-M1     | Image variants melalui queue                      |
| XC-M2     | EXIF stripping                                    |
| XC-M3     | Server-side MIME validation                       |
| XC-S1     | Server-side HTML sanitization                     |
| XC-E2     | 404/500 berbahasa Indonesia                       |
| NFR-SEC   | Security foundation                               |
| NFR-I18N  | Bahasa Indonesia + WIB                            |
| NFR-MAINT | Test dan maintainability                          |

---

# 3. Scope

## 3.1 In Scope

### A. Project Baseline

- Laravel 13 existing project verification.
- PHP version compatibility.
- Composer dependency verification.
- Node.js/npm/pnpm compatibility sesuai repository.
- Vite configuration verification.
- Tailwind CSS v4 verification.
- Inertia React verification.
- shadcn/ui foundation verification.
- `.env.example`.
- `.gitignore`.
- clean starter/demo artifacts yang tidak diperlukan.
- baseline application boot.

### B. Laravel Foundation

- `app.timezone = UTC`.
- `app.display_timezone = Asia/Pontianak`.
- `app.locale = id`.
- `app.fallback_locale = id` atau konfigurasi yang konsisten dengan localization strategy.
- language resources Bahasa Indonesia.
- application service providers.
- filesystem configuration.
- session configuration.
- cache configuration.
- queue configuration.
- logging baseline.
- development/production environment conventions.

### C. Database Foundation

System tables dan application foundation:

- `users`
- `password_reset_tokens`
- `sessions`
- `jobs`
- `failed_jobs`
- `cache`
- `cache_locks`
- `site_settings`
- Spatie Permission tables

`users` wajib memiliki:

- `name`
- `email`
- `password`
- `is_active`
- `email_verified_at`
- `remember_token`
- timestamps

**MUST NOT:**

```text
users.role
```

Role dan permission berasal dari Spatie Laravel Permission.

### D. Docker Development Environment

Minimal:

- MySQL
- application runtime yang diperlukan oleh repository

Opsional:

- Redis
- Mailpit

Redis dan service tambahan hanya digunakan apabila memang diperlukan oleh implementasi. Database queue/cache tetap diperbolehkan untuk MVP.

### E. RBAC Foundation

Spatie Laravel Permission wajib digunakan sejak MVP.

MVP hanya memiliki role:

```text
super_admin
```

Permission menggunakan pola:

```text
resource.action
```

Contoh:

```text
posts.view
posts.create
posts.update
posts.delete

events.view
events.create
events.update
events.delete

settings.view
settings.update
```

Permission dasar boleh didefinisikan sejak Phase 01 sebagai fondasi, tetapi UI Role Management dan Permission Management belum boleh dibuat.

### F. Authentication

Admin authentication:

- `/admin/login`
- logout
- forgot password
- reset password
- login rate limiting
- inactive account protection
- no public registration

### G. Authorization

Authorization harus menggunakan:

1. Spatie Permission untuk role/permission assignment;
2. Laravel Policy/Gate untuk contextual authorization.

Tidak boleh membuat authorization system kedua berbasis custom `users.role`.

### H. Inertia + React Foundation

- Inertia application bootstrap.
- shared props.
- authenticated user.
- flash messages.
- validation errors.
- page title.
- locale.
- display timezone.
- public/admin page structure.
- TypeScript types dasar.

### I. UI Foundation

Basic reusable components:

- Button
- Input
- Textarea
- Select
- Checkbox
- Card
- Badge
- Dialog
- ConfirmDialog
- Dropdown
- Alert
- Toast
- Table
- Pagination
- EmptyState
- Loading/Skeleton

### J. Layout Foundation

Public:

```text
PublicLayout
```

Admin:

```text
AdminLayout
```

Phase 01 hanya membuat shell/foundation.

Implementasi final visual berdasarkan `DESIGN-hspmtb.md` dilakukan pada phase UI/public website berikutnya.

### K. Site Settings

Implement:

- `site_settings` migration.
- model.
- service.
- cache.
- admin settings page.

Settings service harus menjadi single access point untuk application settings.

### L. Media Foundation

Implement:

- image upload validation;
- MIME validation;
- size validation;
- EXIF stripping;
- WebP conversion;
- image variants;
- width/height extraction;
- queue processing;
- storage abstraction;
- upload status/progress foundation.

### M. HTML Sanitization

Implement:

- server-side sanitizer;
- Tiptap React foundation;
- allowlist HTML;
- safe links;
- safe image attributes;
- XSS protection.

### N. Queue / Cache / Scheduler

Foundation:

- database queue;
- failed jobs;
- queue job structure;
- cache;
- scheduler;
- command/job conventions.

Phase 01 belum mengimplementasikan business scheduler seperti publishing scheduled posts.

### O. Error / SEO Foundation

Implement:

- 404 page;
- 500 page;
- Indonesian error messages;
- Inertia `Head`;
- basic SEO component structure;
- Open Graph foundation.

### P. Testing & Quality

Backend:

- Pest/PHPUnit;
- Feature tests;
- Unit tests.

Static/code quality:

- Laravel Pint;
- Larastan;
- ESLint;
- TypeScript;
- frontend formatting/checks.

### Q. Git Workflow

Implement:

- Husky;
- Commitlint;
- Conventional Commits;
- pre-commit checks;
- commit-msg validation.

---

# 4. Out of Scope

Phase 01 **MUST NOT** mengimplementasikan modul bisnis berikut:

- Jadwal Misa;
- Berita;
- Agenda;
- Profil Paroki;
- Pelayanan;
- Komunitas;
- Galeri;
- Kontak;
- Download;
- final Homepage;
- final Admin Dashboard;
- sitemap final;
- robots final;
- Role Management UI;
- Permission Management UI;
- role `admin_content`;
- role `editor`;
- approval workflow;
- akun umat;
- public registration;
- contact form;
- RSVP;
- comments;
- payment;
- newsletter;
- donation;
- 2FA;
- audit trail;
- global search.

Fondasi teknis yang diperlukan untuk fitur tersebut boleh disiapkan, tetapi fitur bisnisnya belum boleh dibangun.

---

# 5. Prinsip Implementasi

## 5.1 Jangan Membuat Ulang Project

Repository sudah merupakan Laravel project.

Agent:

- **MUST NOT** menjalankan `laravel new`;
- **MUST NOT** mengganti project dengan skeleton baru;
- **MUST** mempertahankan repository dan konfigurasi yang sudah ada kecuali memang diperlukan.

---

## 5.2 Standard Architecture

Gunakan standard Laravel architecture.

```text
app/
├── Actions/
├── Console/
│   ├── Commands/
│   └── Kernel.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Public/
│   ├── Middleware/
│   └── Requests/
├── Jobs/
├── Models/
├── Policies/
├── Providers/
└── Services/
```

Tidak perlu:

- Repository layer;
- DTO framework;
- enterprise service bus;
- excessive abstraction;
- generic CRUD framework.

Gunakan Service/Action hanya ketika logic memang memiliki tanggung jawab yang jelas.

---

# 6. Workstream 01 — Baseline & Environment

## Objective

Memastikan repository yang ada dapat dijalankan secara konsisten.

## Tasks

### 01.1 Inspect Existing Project

Periksa:

```text
composer.json
package.json
vite.config.*
tsconfig.json
resources/js
resources/css
routes
app
database
tests
.github
```

Catat:

- Laravel version;
- PHP requirement;
- Inertia version;
- React version;
- Tailwind version;
- Vite version;
- testing tools;
- existing authentication package.

### 01.2 Verify Composer

Pastikan dependency tidak memiliki konflik.

Commands:

```bash
composer validate
composer install
composer check-platform-reqs
```

### 01.3 Verify Frontend

```bash
npm install
npm run build
```

Jika repository menggunakan package manager lain, ikuti lockfile yang sudah ada.

### 01.4 Verify Application

```bash
php artisan about
php artisan route:list
php artisan migrate:status
```

### Acceptance Criteria

- application dapat boot;
- Composer install berhasil;
- frontend build berhasil;
- Artisan tidak error;
- existing tests dapat dijalankan.

---

# 7. Workstream 02 — Laravel Application Foundation

## 7.1 Timezone

`config/app.php`:

```php
'timezone' => 'UTC',
'display_timezone' => 'Asia/Pontianak',
```

Jangan mengubah storage timezone menjadi WIB.

### Rule

```text
Database datetime → UTC
Display datetime → Asia/Pontianak
Weekly mass time → wall-clock WIB
```

## 7.2 Locale

Gunakan:

```php
'locale' => 'id',
```

UI dan validation message menggunakan Bahasa Indonesia.

## 7.3 Language Resources

Minimal:

```text
lang/
└── id/
    ├── auth.php
    ├── pagination.php
    ├── passwords.php
    └── validation.php
```

Jika aplikasi menggunakan struktur translation tambahan, pertahankan struktur yang konsisten.

## Acceptance Criteria

- `config('app.timezone') === 'UTC'`;
- `config('app.display_timezone') === 'Asia/Pontianak'`;
- `config('app.locale') === 'id'`;
- validation error dapat tampil dalam Bahasa Indonesia.

---

# 8. Workstream 03 — Database Foundation

## 8.1 Users

Migration harus memiliki:

```text
users
├── id
├── name
├── email UNIQUE
├── email_verified_at NULL
├── password
├── is_active BOOLEAN DEFAULT TRUE
├── remember_token
├── created_at
└── updated_at
```

Jangan tambahkan:

```text
role
```

## 8.2 Site Settings

Schema:

```text
site_settings
├── id
├── key VARCHAR(100) UNIQUE
├── value LONGTEXT NULL
├── group VARCHAR(50) NULL
├── created_at
└── updated_at
```

## 8.3 Laravel System Tables

Pastikan tersedia sesuai kebutuhan:

```text
password_reset_tokens
sessions
jobs
failed_jobs
cache
cache_locks
```

## 8.4 Migration Rules

- Foreign key harus eksplisit.
- Index harus memiliki alasan.
- Jangan membuat migration untuk modul Phase 02+.
- Migration harus idempotent dalam konteks fresh installation.
- `php artisan migrate:fresh --seed` harus dapat digunakan di development.

---

# 9. Workstream 04 — Docker Development Environment

## Objective

Developer dapat menjalankan database secara konsisten tanpa instalasi MySQL manual.

Minimal service:

```text
mysql
```

Contoh environment:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=hspmtb
DB_USERNAME=...
DB_PASSWORD=...
```

Jika aplikasi dijalankan di host dan MySQL di Docker, gunakan host mapping yang sesuai.

## Requirements

- persistent volume;
- healthcheck;
- UTF-8/utf8mb4;
- timezone handling tidak boleh mengubah keputusan D-01;
- credentials tidak boleh di-hard-code di source.

## Optional Services

Mailpit dan Redis boleh ditambahkan bila dibutuhkan.

Jangan menambah infrastructure hanya demi kompleksitas.

---

# 10. Workstream 05 — Spatie Permission / RBAC Foundation

## 10.1 Install

Gunakan versi Spatie yang kompatibel dengan Laravel 13.

Verifikasi dokumentasi resmi/package compatibility sebelum instalasi.

## 10.2 User Model

Tambahkan:

```php
use Spatie\Permission\Traits\HasRoles;
```

dan:

```php
use HasRoles;
```

## 10.3 Role

Seed:

```text
super_admin
```

Role ini merupakan system role.

## 10.4 Permissions

Gunakan naming:

```text
resource.action
```

Contoh foundation:

```text
dashboard.view

settings.view
settings.update

users.view
users.create
users.update
users.disable

posts.view
posts.create
posts.update
posts.delete
```

Agent boleh menentukan daftar permission foundation yang diperlukan selama:

- naming konsisten;
- tidak membuat role tambahan;
- tidak membangun UI permission management.

## 10.5 Guard Consistency

Pastikan guard Spatie konsisten dengan guard authentication yang digunakan aplikasi.

Jangan menghasilkan kondisi:

```text
auth menggunakan web
permission menggunakan guard lain
```

tanpa alasan yang terdokumentasi.

## 10.6 Super Admin

`super_admin` harus memiliki akses penuh melalui Spatie.

Implementasi dapat menggunakan role assignment:

```php
$user->assignRole('super_admin');
```

Authorization tetap dapat menggunakan:

```php
$user->hasRole('super_admin');
$user->can('posts.view');
```

dan Policy/Gate untuk aturan contextual.

## 10.7 Prohibited

Jangan membuat:

```php
$user->role
```

sebagai sumber authorization.

Jangan membuat:

```text
RoleManagementController
PermissionManagementController
RoleManagementPage
PermissionManagementPage
```

pada Phase 01.

---

# 11. Workstream 06 — Super Admin Seeder

## Objective

Fresh installation harus dapat menghasilkan akun Super Admin secara aman.

Credentials berasal dari `.env`.

Contoh:

```env
ADMIN_NAME=
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

Jangan hard-code password.

## Seeder Requirements

Seeder harus:

1. mencari/membuat user berdasarkan email;
2. memastikan `is_active = true`;
3. memastikan password di-hash;
4. memastikan role `super_admin`;
5. idempotent;
6. tidak membuat akun duplikat.

## Security

`.env`:

- tidak boleh committed;
- password tidak boleh masuk source;
- password tidak boleh ditulis ke log.

---

# 12. Workstream 07 — Authentication

## Objective

Menyediakan authentication khusus admin.

## Routes

Public authentication:

```text
/admin/login
```

Protected:

```text
/admin/*
```

Exception:

```text
/admin/login
/admin/forgot-password
/admin/reset-password/*
```

## Required Features

- login;
- logout;
- forgot password;
- reset password;
- rate limiting;
- inactive user protection.

## No Registration

Public registration harus dihapus/nonaktif.

Tidak boleh ada:

```text
/register
```

yang dapat digunakan publik.

## Inactive Account

Jika:

```text
users.is_active = false
```

user tidak dapat login.

## Last Active Admin

Foundation authorization harus mendukung rule PRD:

> Tidak boleh menonaktifkan akun Super Admin aktif terakhir.

Implementasi UI account management lengkap dapat dikerjakan pada phase berikutnya, tetapi policy/service rule tidak boleh dirancang bertentangan dengan requirement ini.

---

# 13. Workstream 08 — Admin Authorization

## Middleware / Gate

Semua:

```text
/admin/*
```

harus membutuhkan:

1. authentication;
2. active account;
3. authorization.

## MVP Rule

Hanya:

```text
super_admin
```

yang dapat masuk admin.

## Recommended Pattern

Gunakan middleware/authorization yang jelas, misalnya:

```text
auth
active account check
permission/role check
```

Jangan menyebarkan:

```php
if ($user->role === ...)
```

di controller.

## Policy

Gunakan Policy jika authorization membutuhkan object/resource context.

Contoh:

```text
PostPolicy
EventPolicy
DocumentPolicy
```

Policy bisnis modul dapat dibuat saat modulnya dibangun.

Phase 01 cukup menyiapkan authorization convention dan system-level policy foundation.

---

# 14. Workstream 09 — Inertia + React Foundation

## Application Structure

```text
resources/js/
├── Components/
├── Features/
├── Hooks/
├── Layouts/
│   ├── PublicLayout.tsx
│   └── AdminLayout.tsx
├── Lib/
├── Pages/
│   ├── Admin/
│   └── Public/
├── Types/
├── app.tsx
└── bootstrap.ts
```

Sesuaikan extension dengan repository.

## Shared Props

Minimal:

```text
auth.user
flash
errors
locale
displayTimezone
```

Jangan mengirim seluruh model User ke frontend.

Expose hanya data yang diperlukan.

Contoh:

```ts
{
    id: number;
    name: string;
    email: string;
}
```

## Flash Message

Support:

```text
success
error
warning
info
```

---

# 15. Workstream 10 — UI Foundation

## Components

Buat atau konfigurasi ulang component primitives:

```text
Button
Input
Textarea
Select
Checkbox
Card
Badge
Dialog
ConfirmDialog
Dropdown
Alert
Toast
Table
Pagination
EmptyState
Loading
Skeleton
```

## Rules

- accessibility;
- keyboard navigation;
- focus states;
- minimum touch target 44px;
- no hard-coded random colors;
- Tailwind tokens;
- no premature page-specific abstractions.

## Important

Phase 01 tidak mengimplementasikan visual final website HSPMTB.

`DESIGN-hspmtb.md` menjadi acuan visual utama pada phase UI/public website.

---

# 16. Workstream 11 — PublicLayout

## Required Structure

```text
PublicLayout
├── Header
├── Navigation
├── Main
└── Footer
```

Header foundation:

- logo placeholder;
- navigation placeholder;
- mobile menu;
- CTA Jadwal Misa placeholder.

Footer foundation:

- parish identity placeholder;
- quick links;
- contact placeholder;
- social placeholder;
- privacy placeholder;
- copyright.

Jangan memasukkan data nyata paroki jika belum dikonfirmasi.

Gunakan:

```text
[ISI: ...]
```

untuk placeholder.

---

# 17. Workstream 12 — AdminLayout

## Required Structure

```text
AdminLayout
├── Sidebar
├── Header
├── Breadcrumb
├── Main
└── Flash/Toast
```

Foundation navigation boleh menggunakan placeholder:

```text
Dashboard
Beranda
Profil
Jadwal Misa
Berita
Agenda
Pelayanan
Komunitas
Galeri
Kontak
Download
Pengaturan
Akun
```

Namun route/module yang belum dibangun tidak boleh dianggap selesai hanya karena menu ditampilkan.

---

# 18. Workstream 13 — Site Settings

## Service

Buat:

```text
SiteSettingsService
```

Responsibilities:

- get setting;
- get group;
- set setting;
- bulk update;
- cache;
- invalidate cache.

## Example API

Konsep:

```php
$settings->get('site_name');
$settings->getGroup('contact');
$settings->set('site_name', $value);
```

Implementasi konkret boleh disesuaikan dengan architecture.

## Cache

Settings harus di-cache.

Saat update:

```text
update DB
→ invalidate settings cache
→ return fresh data
```

## Admin Page

Phase 01 hanya membutuhkan foundation `/admin/pengaturan`.

Settings minimal dapat mencakup:

```text
identity
contact
social
seo
homepage
```

Jangan mengimplementasikan seluruh field final jika belum dibutuhkan.

---

# 19. Workstream 14 — Storage Foundation

## Disks

Minimal:

```text
public
private/local
```

### Public

Untuk:

- published image variants;
- public images.

### Private

Untuk:

- original files;
- documents;
- private assets.

## Security

Dokumen tidak boleh diletakkan di public disk jika PRD mensyaratkan controlled download.

---

# 20. Workstream 15 — Image Processing

## Requirements

Image pipeline wajib:

1. validate MIME;
2. validate size;
3. decode safely;
4. strip EXIF;
5. generate variants;
6. convert WebP;
7. store variants;
8. persist metadata;
9. execute processing through queue.

## Example Variants

```text
thumb
medium
large
```

Ukuran final dapat ditentukan saat modul media konkret dibangun.

## Metadata

Simpan:

```text
width
height
file_size
mime_type
path
```

## EXIF

EXIF GPS harus dihapus.

---

# 21. Workstream 16 — Queue Media Job

Create:

```text
GenerateImageVariants
```

Job responsibilities:

- read uploaded image;
- process variants;
- strip metadata;
- save output;
- update processing status;
- handle failure.

## Failure Handling

Job failure harus:

- masuk `failed_jobs`;
- tidak merusak database;
- tidak menyebabkan request upload timeout;
- memiliki log yang berguna.

---

# 22. Workstream 17 — HTML Sanitizer

## Objective

Mencegah XSS dari rich text.

Semua HTML dari:

- Tiptap;
- profile content;
- article content;
- description;
- rich text fields;

harus disanitasi server-side.

## Allowlist

Minimal mendukung kebutuhan PRD:

```text
p
br
strong
em
u
ul
ol
li
h2
h3
h4
blockquote
a
img
figure
figcaption
table
```

Attribute harus dibatasi.

Links harus aman.

External links menggunakan:

```text
rel="noopener noreferrer"
```

## Important

Client-side sanitization bukan pengganti server-side sanitization.

---

# 23. Workstream 18 — Tiptap Foundation

Install/configure Tiptap React yang kompatibel dengan project.

Foundation editor:

- paragraph;
- heading;
- bold;
- italic;
- underline;
- lists;
- blockquote;
- link;
- image bila dibutuhkan;
- table bila dibutuhkan.

Editor harus menghasilkan HTML yang kemudian disanitasi server.

Jangan membangun full article editor pada Phase 01.

---

# 24. Workstream 19 — Cache Foundation

Gunakan Laravel Cache.

Minimal support:

```text
site settings
```

Cache key harus konsisten.

Contoh:

```text
site_settings
site_settings:{group}
```

Jangan cache data dinamis seperti `next mass` secara sembarangan.

---

# 25. Workstream 20 — Scheduler Foundation

Pastikan scheduler Laravel siap.

Phase 01 hanya menyiapkan infrastructure.

Business command berikut dikerjakan pada Phase 02:

```text
PublishScheduledPosts
```

Jangan mengimplementasikan publishing berita pada Phase 01.

---

# 26. Workstream 21 — Error Handling

## Required Pages

```text
404
500
```

Bahasa:

```text
Indonesia
```

Public errors menggunakan:

```text
PublicLayout
```

Admin errors dapat menggunakan admin shell jika konteksnya sesuai.

## UX

404 minimal:

- heading;
- explanatory message;
- link ke Beranda.

500 minimal:

- heading;
- explanatory message;
- retry/back link.

Jangan membocorkan stack trace di production.

---

# 27. Workstream 22 — SEO Foundation

Phase 01 hanya menyediakan reusable foundation.

## Component

Konsep:

```text
SEO
```

Props minimal:

```text
title
description
canonical
ogImage
```

Gunakan Inertia `Head`.

Final SEO:

- sitemap;
- robots;
- structured data;
- page-specific metadata;

dikerjakan pada phase berikutnya.

---

# 28. Workstream 23 — Testing Foundation

## Backend

Gunakan Pest atau PHPUnit sesuai repository.

Test categories:

```text
tests/
├── Feature/
└── Unit/
```

## Mandatory Phase 01 Tests

### Application

- application boots;
- database connection;
- migrations pass.

### Configuration

- timezone UTC;
- display timezone WIB;
- locale id.

### User

- `is_active` exists;
- default active;
- no custom role field.

### Authentication

- guest can see login;
- valid credentials login;
- invalid credentials rejected;
- rate limiting;
- inactive account rejected;
- logout works;
- registration unavailable.

### RBAC

- `super_admin` role exists;
- user can receive `super_admin`;
- authorization uses Spatie;
- user without role cannot access admin;
- admin route rejects guest;
- no custom `users.role`.

### Settings

- settings can be read;
- settings can be updated;
- cache invalidates.

### Sanitization

- dangerous HTML is removed;
- safe HTML remains;
- unsafe link attributes are removed/normalized.

### Media

- invalid MIME rejected;
- oversized image rejected;
- processing job can run;
- output WebP exists;
- EXIF is removed.

---

# 29. Workstream 24 — Frontend Quality

Minimum:

```text
TypeScript check
ESLint
production build
```

No TypeScript errors.

No ESLint errors that block CI.

Avoid introducing `any` unless justified.

---

# 30. Workstream 25 — Git / Husky / Commitlint

## Commit Convention

Use Conventional Commits:

```text
feat:
fix:
refactor:
docs:
test:
chore:
build:
ci:
perf:
style:
```

Examples:

```text
feat(auth): add admin authentication foundation
feat(rbac): add super admin role
feat(settings): add site settings service
test(auth): cover inactive account login
chore(dev): configure docker mysql
```

## Commitlint

Commit message must be validated.

## Husky

Minimum hooks:

```text
pre-commit
commit-msg
```

`pre-commit` should run only checks that are fast enough for local development.

---

# 31. Workstream 26 — Documentation

Create/update:

```text
docs/
├── DECISIONS.md
├── DEVELOPMENT.md
└── RBAC.md
```

## DEVELOPMENT.md

Document:

- requirements;
- installation;
- `.env`;
- Docker;
- database setup;
- migrations;
- seed;
- frontend setup;
- tests;
- queue worker;
- scheduler.

## RBAC.md

Document:

- Spatie role;
- permission naming;
- super_admin;
- Policy/Gate convention;
- prohibition of `users.role`;
- future role extension strategy.

## DECISIONS.md

Document only decisions that matter.

Examples:

```text
UTC storage
Asia/Pontianak display
Spatie Permission
database queue
private document storage
```

---

# 32. Expected Directory Structure After Phase 01

Target structure:

```text
app/
├── Actions/
├── Console/
│   └── Commands/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Public/
│   ├── Middleware/
│   └── Requests/
├── Jobs/
├── Models/
│   └── User.php
├── Policies/
├── Providers/
└── Services/
    ├── SiteSettingsService.php
    ├── ImageProcessor.php
    └── HtmlSanitizer.php

config/
├── app.php
└── ...

database/
├── factories/
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php
    ├── SuperAdminSeeder.php
    └── PermissionSeeder.php

docs/
├── DECISIONS.md
├── DEVELOPMENT.md
└── RBAC.md

resources/
├── css/
└── js/
    ├── Components/
    ├── Features/
    ├── Hooks/
    ├── Layouts/
    │   ├── AdminLayout.tsx
    │   └── PublicLayout.tsx
    ├── Lib/
    ├── Pages/
    │   ├── Admin/
    │   └── Public/
    ├── Types/
    └── app.tsx

routes/
├── console.php
└── web.php

tests/
├── Feature/
└── Unit/
```

Actual tree may differ slightly according to existing Laravel/Inertia starter structure.

Do not force unnecessary restructuring.

---

# 33. Task Breakdown

## P01 — Baseline

- [x] Inspect repository.
- [x] Verify versions.
- [x] Verify Composer.
- [x] Verify frontend.
- [x] Verify existing tests.
- [ ] Remove unnecessary starter artifacts. — *belum*: `.codex/config.toml` (membocorkan path absolut mesin) dan `pnpm-workspace.yaml` (proyek memakai npm) masih ikut ter-track. Keduanya sudah ada di `main` sejak initial commit.

## P02 — Laravel Config

- [x] UTC timezone.
- [x] display timezone.
- [x] locale.
- [x] Indonesian translations.
- [x] environment configuration.

## P03 — Database

- [x] users.
- [x] is_active.
- [x] site_settings.
- [x] system tables.
- [x] migration verification.

## P04 — Docker

> **Dibatalkan.** Docker tidak dipakai; MySQL 8 berjalan native di host. Semua
> butir di bawah tidak dikerjakan. Lihat `docs/DECISIONS.md` D-15.

- [~] MySQL. — dibatalkan (D-15)
- [~] volume. — dibatalkan (D-15)
- [~] healthcheck. — digantikan CI: service MySQL + `--health-cmd` di `.github/workflows/tests.yml`
- [~] UTF-8. — dijamin level driver (`utf8mb4_0900_ai_ci`), diuji di `DatabaseFoundationTest`
- [~] `.env` configuration. — `DB_HOST=127.0.0.1`, tanpa container

## P05 — RBAC

- [x] Install Spatie.
- [x] publish migration/config.
- [x] User HasRoles.
- [x] PermissionSeeder.
- [x] SuperAdminSeeder.
- [x] guard consistency.
- [x] authorization tests.

## P06 — Authentication

- [x] login.
- [x] logout.
- [x] forgot password.
- [x] reset password.
- [x] rate limiting.
- [x] inactive account protection.
- [x] remove registration.

## P07 — Inertia Foundation

- [x] shared props.
- [x] auth props.
- [x] flash props.
- [x] errors.
- [x] TypeScript types.
- [x] page structure. — pemisahan `pages/{public,admin}` sudah ada; `PublicLayout`/`AdminLayout` sendiri adalah P08b.

## P08 — UI

- [x] primitives. — 11 dari §15 sudah ada dari starter; `textarea`, `table`, dan
  `pagination` diinstal dari registry (0 dependency baru). `ConfirmDialog` dan
  `EmptyState` tidak ada di registry dan dibuat custom.
- [x] PublicLayout. — shell saja: `ParishNavbar` + `<Main>` + `ParishFooter`.
  Seluruh data paroki berupa placeholder `[ISI: ...]`.
- [x] AdminLayout. — sidebar 12 item §17; hanya Dashboard/Pengaturan/Akun yang
  punya route, sisanya nonaktif dan diberi label "belum".
- [x] responsive shell. — Sheet untuk nav publik < lg, sidebar collapsible,
  token tipografi dan spacing responsif sesuai DESIGN.md.
- [x] accessibility foundation. — target sentuh 44px, focus ring terpusat,
  `aria-label` pada trigger, skip-link ke konten utama.

> **Catatan cakupan.** Yang dikerjakan adalah *fondasi* visual, bukan tampilan
> final. DESIGN.md §15 menyatakan Phase 01 tidak mengimplementasikan visual final
> website HSPMTB; komposisi finalnya milik phase UI/public website berikutnya.
> Penyimpangan terhadap DESIGN.md tercatat di `docs/DECISIONS.md` D-22.

## P09 — Settings

- [ ] SiteSettings model.
- [ ] service.
- [ ] cache.
- [ ] admin page.
- [ ] tests.

## P10 — Media

- [ ] storage.
- [ ] MIME validation.
- [ ] image processor.
- [ ] WebP.
- [ ] variants.
- [ ] EXIF stripping.
- [ ] queue job.
- [ ] tests.

## P11 — Sanitization

- [ ] sanitizer.
- [ ] Tiptap.
- [ ] allowlist.
- [ ] XSS tests.

## P12 — Infrastructure

- [ ] queue.
- [ ] cache.
- [ ] scheduler.
- [ ] failed jobs.
- [ ] error pages.
- [ ] SEO component.

## P13 — Quality

- [ ] Pest/PHPUnit.
- [ ] Pint.
- [ ] Larastan.
- [ ] ESLint.
- [ ] TypeScript.
- [ ] build.

## P14 — Git

- [ ] Husky.
- [ ] Commitlint.
- [ ] Conventional Commits.
- [ ] hooks.

## P15 — Documentation

- [ ] DEVELOPMENT.md.
- [ ] RBAC.md.
- [ ] DECISIONS.md.

---

# 34. Dependency Order

Agent MUST follow this order:

```text
P01 Baseline
  ↓
P02 Laravel Config
  ↓
P03 Database
  ↓
P04 Docker
  ↓
P05 RBAC
  ↓
P06 Authentication
  ↓
P07 Inertia
  ↓
P08 UI
  ↓
P09 Settings
  ↓
P10 Media
  ↓
P11 Sanitization
  ↓
P12 Infrastructure
  ↓
P13 Quality
  ↓
P14 Git
  ↓
P15 Documentation
```

Some independent tasks may be executed in parallel if dependency safety is maintained.

---

# 35. Test Matrix

| ID  | Test                        | Expected                           |
| --- | --------------------------- | ---------------------------------- |
| T01 | Application boot            | Pass                               |
| T02 | Database connection         | Pass                               |
| T03 | Migration                   | Pass                               |
| T04 | Timezone                    | UTC                                |
| T05 | Display timezone            | Asia/Pontianak                     |
| T06 | Locale                      | id                                 |
| T07 | User active default         | true                               |
| T08 | User has no role column     | Pass                               |
| T09 | Spatie role exists          | `super_admin`                      |
| T10 | Super Admin assignment      | Pass                               |
| T11 | Guest admin access          | 302/401/403 according to auth flow |
| T12 | Non-role user admin access  | 403                                |
| T13 | Inactive login              | Rejected                           |
| T14 | Registration                | Not available                      |
| T15 | Login rate limit            | Enforced                           |
| T16 | Settings read               | Pass                               |
| T17 | Settings update             | Pass                               |
| T18 | Settings cache invalidation | Pass                               |
| T19 | Invalid image MIME          | Rejected                           |
| T20 | Oversized image             | Rejected                           |
| T21 | Image processing            | Pass                               |
| T22 | WebP generated              | Pass                               |
| T23 | EXIF stripped               | Pass                               |
| T24 | Sanitizer XSS               | Removed                            |
| T25 | Safe HTML                   | Preserved                          |
| T26 | Queue failed job            | Recorded                           |
| T27 | 404                         | Indonesian                         |
| T28 | 500                         | Indonesian                         |
| T29 | TypeScript                  | Pass                               |
| T30 | ESLint                      | Pass                               |
| T31 | Pint                        | Pass                               |
| T32 | Larastan                    | Pass                               |
| T33 | Production build            | Pass                               |
| T34 | Fresh install + seed        | Pass                               |

---

# 36. Acceptance Criteria

Phase 01 dianggap selesai hanya jika seluruh acceptance criteria berikut terpenuhi.

## AC-01 — Application

A fresh developer environment dapat menjalankan application.

```text
install
→ configure .env
→ migrate
→ seed
→ build
→ serve
```

tanpa error.

## AC-02 — Database

Database berhasil dibuat dengan migration.

`users` memiliki `is_active`.

`users` tidak memiliki `role`.

## AC-03 — Timezone

Application:

```text
storage = UTC
display = Asia/Pontianak
```

## AC-04 — Localization

UI dan validation foundation menggunakan Bahasa Indonesia.

## AC-05 — RBAC

Spatie Permission aktif.

Role:

```text
super_admin
```

tersedia.

## AC-06 — Super Admin

Super Admin dapat dibuat melalui environment-driven seeder tanpa hard-coded password.

## AC-07 — Authentication

Super Admin dapat:

```text
login
logout
forgot password
reset password
```

Registration publik tidak tersedia.

## AC-08 — Authorization

Guest tidak dapat membuka admin.

User tanpa `super_admin` tidak dapat membuka admin MVP.

Inactive user tidak dapat login.

## AC-09 — Settings

Super Admin dapat membaca dan mengubah site settings.

Cache settings di-invalidasi setelah perubahan.

## AC-10 — Media

Admin foundation dapat menerima gambar yang:

- MIME valid;
- ukuran valid;
- EXIF dihapus;
- dikonversi WebP;
- memiliki variant;
- diproses melalui queue.

## AC-11 — Sanitization

HTML berbahaya tidak dapat tersimpan sebagai executable XSS.

## AC-12 — UI Foundation

PublicLayout dan AdminLayout tersedia dan responsif.

## AC-13 — Error

404 dan 500 menggunakan Bahasa Indonesia.

## AC-14 — Quality

Automated checks pass.

Minimal:

```text
tests
lint
typecheck
build
```

## AC-15 — Documentation

Developer baru dapat mengikuti `docs/DEVELOPMENT.md` untuk menjalankan project.

## AC-16 — Scope

Tidak ada modul bisnis Phase 02+ yang dianggap selesai atau dibangun secara penuh.

---

# 37. Definition of Done

Phase 01 DONE jika:

- [ ] Laravel 13 project dapat boot.
- [ ] MySQL dapat dijalankan.
- [ ] Migration berhasil.
- [ ] Seed berhasil.
- [ ] timezone UTC diterapkan.
- [ ] display timezone Asia/Pontianak tersedia.
- [ ] locale `id` tersedia.
- [ ] `users.is_active` tersedia.
- [ ] tidak ada `users.role`.
- [ ] Spatie Permission aktif.
- [ ] `super_admin` aktif.
- [ ] permission foundation tersedia.
- [ ] authentication admin bekerja.
- [ ] public registration disabled.
- [ ] inactive account ditolak.
- [ ] admin authorization bekerja.
- [ ] Inertia React foundation bekerja.
- [ ] PublicLayout tersedia.
- [ ] AdminLayout tersedia.
- [ ] basic UI primitives tersedia.
- [ ] site settings service bekerja.
- [ ] site settings cache bekerja.
- [ ] media validation bekerja.
- [ ] EXIF stripping bekerja.
- [ ] WebP conversion bekerja.
- [ ] image variants bekerja.
- [ ] queue job bekerja.
- [ ] HTML sanitizer bekerja.
- [ ] Tiptap foundation tersedia.
- [ ] 404 tersedia.
- [ ] 500 tersedia.
- [ ] SEO foundation tersedia.
- [ ] tests hijau.
- [ ] static analysis hijau.
- [ ] frontend checks hijau.
- [ ] production build berhasil.
- [ ] Husky aktif.
- [ ] Commitlint aktif.
- [ ] documentation foundation tersedia.

---

# 38. Security Checklist

Sebelum menutup Phase 01, agent harus memeriksa:

- [ ] `.env` tidak tracked.
- [ ] password tidak hard-coded.
- [ ] password tidak masuk log.
- [ ] authentication menggunakan Laravel mechanism.
- [ ] rate limit login aktif.
- [ ] inactive users ditolak.
- [ ] authorization tidak menggunakan custom `users.role`.
- [ ] Spatie guard konsisten.
- [ ] mass assignment dikontrol.
- [ ] CSRF aktif.
- [ ] validation dilakukan server-side.
- [ ] uploaded MIME divalidasi server-side.
- [ ] dangerous HTML disanitasi.
- [ ] EXIF/GPS dihapus.
- [ ] private files tidak public.
- [ ] production debug tidak expose stack trace.
- [ ] security headers foundation tidak merusak aplikasi.

---

# 39. AI Agent Guardrails

Agent yang mengerjakan Phase 01 wajib mengikuti aturan berikut.

## MUST

- membaca PRD sebelum implementasi;
- mengikuti requirement ID;
- membuat test untuk acceptance criteria;
- menggunakan Spatie Permission;
- menggunakan `users.is_active`;
- menggunakan UTC untuk database datetime;
- menggunakan Asia/Pontianak untuk display;
- menggunakan Form Request untuk validation;
- menggunakan Policy/Gate untuk contextual authorization;
- menggunakan Eloquent;
- menjaga controller tetap tipis;
- mendokumentasikan keputusan arsitektur yang tidak eksplisit di PRD.

## MUST NOT

- membuat `users.role`;
- membuat role selain `super_admin`;
- membuat Role Management UI;
- membuat Permission Management UI;
- membuat public registration;
- membuat REST API terpisah;
- membuat repository layer tanpa kebutuhan;
- membuat modul bisnis Phase 02+;
- mengarang data paroki;
- hard-code password;
- bypass server-side sanitization;
- menyimpan dokumen private di public storage;
- mengubah timezone database menjadi Asia/Pontianak;
- menambahkan dependency besar tanpa alasan.

---

# 40. Expected End State

Pada akhir Phase 01, repository harus berada pada kondisi:

```text
Laravel 13
    │
    ├── MySQL
    │
    ├── Authentication
    │       ├── Login
    │       ├── Logout
    │       ├── Forgot Password
    │       └── Reset Password
    │
    ├── Spatie Permission
    │       ├── super_admin
    │       └── permissions
    │
    ├── Inertia
    │       └── React
    │
    ├── UI Foundation
    │       ├── PublicLayout
    │       └── AdminLayout
    │
    ├── Site Settings
    │       └── Cache
    │
    ├── Media Pipeline
    │       ├── MIME validation
    │       ├── EXIF stripping
    │       ├── WebP
    │       ├── Variants
    │       └── Queue
    │
    ├── Content Security
    │       ├── Tiptap
    │       └── HTML Sanitizer
    │
    ├── Error Handling
    │       ├── 404
    │       └── 500
    │
    ├── SEO Foundation
    │
    ├── Tests
    │
    └── Developer Tooling
            ├── Husky
            └── Commitlint
```

Kemudian Phase 02 dapat langsung mulai mengembangkan:

```text
Jadwal Misa
Berita
Agenda
```

tanpa harus membongkar ulang fondasi authentication, RBAC, storage, queue, localization, atau frontend architecture.

---

# 41. Final Phase Gate

Sebelum menyatakan Phase 01 selesai, jalankan checklist:

```bash
composer validate
composer test
composer lint
composer analyse

npm run lint
npm run typecheck
npm run build

php artisan migrate:fresh --seed
php artisan route:list
php artisan queue:work --once
```

Sesuaikan command dengan script aktual yang tersedia pada `composer.json` dan `package.json`.

Kemudian verifikasi manual:

1. buka `/admin/login`;
2. login sebagai Super Admin;
3. akses `/admin`;
4. pastikan user tanpa role ditolak;
5. pastikan user nonaktif tidak dapat login;
6. ubah site setting;
7. upload gambar;
8. pastikan queue memproses gambar;
9. pastikan output WebP tersedia;
10. pastikan EXIF/GPS tidak ada;
11. uji rich text sanitization;
12. buka halaman 404;
13. verifikasi responsive layout;
14. jalankan seluruh test suite.

**Phase 01 hanya boleh diberi status `DONE` jika seluruh gate di atas terpenuhi dan tidak ada requirement MUST yang belum selesai.**
