# RentFlow — Tenant Portal & Building Management

A full-stack, multi-company property management SaaS built with Laravel. RentFlow brings property operations, rent collection, and a mobile tenant portal into one bilingual workspace.

## Problem & solution

Property teams often coordinate leases, spreadsheets, payments, and maintenance across disconnected tools. RentFlow connects each building, unit, tenant, and lease to an authorized company workspace, with clear payment and maintenance histories.

## Features

- Buildings, units, tenants, and lease CRUD with search, filters, pagination, and safe deletion.
- Lease activation, overlap prevention, automatic monthly payment schedules, expiry, and termination.
- Payment recording, overdue tracking, private receipts, and database notifications.
- Maintenance assignment, controlled status transitions, internal notes, audit logs, and private photos.
- Company and super-admin dashboards; mobile tenant dashboard, unit, lease, payments, announcements, documents, and profile.
- Authorized document downloads and Arabic/English contract summary and receipt PDFs.
- Responsive Blade/Tailwind UI, Arabic RTL, English LTR, and a privacy-conscious PWA.

Company, announcement, and document directories currently support browsing/details; documents support protected downloads. Subscription billing and administrative document publishing are outside this demo's scope.

## Tech stack

PHP 8.4 · Laravel 13 · MySQL 8 / InnoDB · Breeze (Blade) · Tailwind CSS 3 · Alpine.js 3 · Spatie Permission 8 · mPDF 8 · Vite 8 · Pest 5 · Playwright.

## Architecture & multi-tenancy

Controllers coordinate HTTP requests; Form Requests validate input; Policies authorize operations. Services own transactional lease, payment, and maintenance workflows. Blade components handle presentation, with eager loading and paginated collections.

A shared database uses `company_id`, foreign keys, composite constraints, and indexes. `CompanyScope` limits queries; middleware and Policies protect routes and records. Company ownership is inferred from the actor or an authorized parent, never trusted from arbitrary request input. Background jobs explicitly enter a trusted tenancy context.

Tenants see their own records. Maintenance employees see assigned requests. Super admins see all companies. Private files live under `storage/app/private`; downloads require authorization and dynamic responses use `private, no-store`.

## User roles & demo accounts

All demo passwords are **`password`**. Accounts are seeded only in local/testing environments.

| Email | Role | Main access |
| --- | --- | --- |
| super@example.com | super_admin | Global oversight |
| admin@example.com | company_admin | Company administration |
| manager@example.com | property_manager | Property and maintenance operations |
| accountant@example.com | accountant | Financial records and collection |
| maintenance@example.com | maintenance_staff | Assigned maintenance requests |
| tenant@example.com | tenant | Personal tenant portal |

Fresh demo: **1 company, 2 buildings, 12 units, 3 tenants, 3 active leases, 36 monthly payments, 3 maintenance requests, 2 announcements, and 1 private document**. Dates remain usable around year boundaries; repeat seeding preserves existing payments.

## Main workflows

1. Create a building and its units; add a tenant.
2. Create a draft or active lease. Activation checks overlaps, occupies the unit, and creates one payment per calendar month touched.
3. Record full payment by cash, bank transfer, or card; generate a private receipt.
4. Submit maintenance → review → assign employee → start work → complete.
5. Tenants follow their payments, maintenance timeline, relevant announcements, and documents.

Due days are clamped to valid month/contract dates. There is no proration or partial-payment accounting. Activated lease terms are locked; termination preserves history and cancels future unpaid installments.

## Installation

Requires PHP 8.4 with `pdo_mysql`, `mbstring`, `gd`, and standard Laravel extensions; Composer; Node.js 22.12+; MySQL 8.

Set PHP `upload_max_filesize=5M` and `post_max_size=8M` or higher to match the maintenance photo limit, then restart the PHP web process. Serve the `public/` directory as the web root.

Create `rentflow` and `rentflow_testing` databases with `utf8mb4`. Copy `.env.example` to `.env`, set `APP_URL`, and configure MySQL credentials.

```sh
composer install
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`. For development, use `composer run dev`. To reset a disposable demo database, use `php artisan migrate:fresh --seed`.

Email defaults to `MAIL_MAILER=log`; verification/reset links appear in `storage/logs/laravel.log`. Registration creates a company and company admin, with email verification required.

Run `php artisan schedule:work` locally, or schedule `php artisan schedule:run` every minute on deployment. The scheduler runs `leases:expire` and `payments:update-overdue` daily. Workflow notifications currently use the database channel.

## AR/EN localization

The visible language switcher changes the session locale. Text lives in `lang/en` and `lang/ar`; layouts use logical spacing and direction-aware navigation. PDFs use DejaVu Sans and mPDF Arabic shaping. Demo names and user-authored content retain their original language. Demo currency is SAR.

## PWA

`npm run build` generates the manifest, icons, service worker, and bilingual offline fallback. Installation needs HTTPS outside localhost; iOS uses Add to Home Screen.

Only explicitly allowed public assets and the generic offline page are cached. Personal pages, contracts, receipts, payment data, photos, and mutations are never stored for offline use. See [PWA configuration](docs/PWA_HYBRID_MODE.md).

## Tests

```sh
php artisan optimize:clear
php artisan test
npm run build
npx playwright test
php artisan route:list
```

Pest uses the separate **rentflow_testing** MySQL database and resets its tables. Browser tests use the local seeded demo and installed Chrome; build and seed before running them. Set `PLAYWRIGHT_CHANNEL` for another supported installed browser. Run builds and browser tests sequentially.

Coverage prioritizes company/tenant isolation, Policies, CRUD, overlapping leases, monthly schedules, payment registration, overdue processing, maintenance transitions, private files/PDFs, localization, and safe offline behavior.

## Screenshots

| Placeholder | Suggested capture |
| --- | --- |
| Company overview | Desktop dashboard with occupancy, revenue, and maintenance |
| Tenant portal | Mobile Arabic dashboard and bottom navigation |
| Property operations | Units table and lease details |
| Maintenance | Assigned request, timeline, and internal notes |
| Documents | Arabic contract summary and payment receipt |

Playwright saves review screenshots under ignored `test-results/`. Use synthetic demo data when selecting portfolio images.

## Future improvements

1. CI with MySQL, browser tests, and repeatable deployment checks.
2. SaaS subscription billing, quotas, and company onboarding controls.
3. Partial payments, adjustments, and financial reconciliation.
4. Document publishing/versioning and configurable email reminders.
5. Operational monitoring, tested backups, and production load testing.

## Further reading

[Database design](docs/DATABASE_DESIGN.md) · [Authorization](docs/AUTHORIZATION.md) · [Tenant portal](docs/TENANT_PORTAL.md) · [User scenarios](docs/USER_SCENARIOS.md) · [Learning notes](docs/LEARNING_NOTES.md) · [Implementation log](docs/IMPLEMENTATION_LOG.md)
