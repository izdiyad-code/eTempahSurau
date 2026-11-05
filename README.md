# eTempahSurau

Laravel application for surau event bookings with Public Booker, Secretary/PIC and Chairman roles.

## Main workflows

- Public facility and availability information, registration and role-based login.
- Booking validation, calendar conflicts, Chairman approval and cancellation/rescheduling requests.
- QR receipt or cash deposit declarations, PIC verification and settlement records.
- Announcements, in-app notifications, dashboards, filtered reports and CSV export.
- English and Bahasa Melayu interface.

## Requirements

PHP 8.2 or later with PDO, pdo_mysql, pdo_sqlite, mbstring, DOM/XML, fileinfo and other standard Laravel extensions; Composer; MySQL/MariaDB. XAMPP can supply the local PHP/database stack.

## Local setup

1. Install dependencies with `composer install`.
2. Copy `.env.example` to `.env` and enter your own local database credentials privately.
3. Create an empty local MySQL/MariaDB database and set the matching database name in `.env`.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate` on the intended empty development database. Review the seeder before optionally seeding disposable demonstration accounts/data.
6. Run `php artisan serve` and open the displayed local URL.

No real database, receipts, logs or .env credentials are included. Do not deploy seeded demonstration accounts as production accounts. Configure real mail delivery privately; default mail logging does not prove inbox delivery.

## Testing

Run `php artisan test` or `php vendor/phpunit/phpunit/phpunit` with the included phpunit.xml configuration. Tests require SQLite :memory:; the test base refuses persistent databases before preparing the schema. Do not use destructive refresh commands against a real database.

Latest recorded local regression result on 6 November 2026: 58 tests, 337 assertions, zero failures/errors. The live browser test separately confirmed a dummy booking through approval, deposit verification and matching CSV.

## Corrected defects

- Refuse deposit verification for cancelled/rejected bookings.
- Refuse approval of elapsed reschedule dates.
- Match login success-message language to the saved/session locale.

This is an academic project. External SMTP delivery, representative UAT and a complete mobile-browser matrix require separate acceptance evidence.

Public source privacy: photographs containing identifiable people are replaced by facility placeholders in this distribution. Local project originals are unchanged.

All raster image assets are omitted from the public distribution and replaced by generated SVG placeholders. Existing local database image paths may require updating; this package is intended for a fresh database setup.
