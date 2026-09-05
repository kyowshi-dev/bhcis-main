<div align="center">

# BHCIS System Sta. Ana

**Barangay Health Center Information System** - a DOH-aligned capstone system for Sta. Ana that complements the barangay's paper-log workflow with digital maternal tracking, child & adult immunization management, consultations & referrals, prescriptions, and barangay health reports.

PHP 8.2+ · Laravel 12 · Blade + Tailwind CSS v4 · MySQL

</div>

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)
    - [Configuration](#configuration)
    - [Default Accounts](#default-accounts)
- [Windows Setup](#windows-setup)
- [Development](#development)
- [Scripts](#scripts)
- [Project Structure](#project-structure)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Features

- **Forgot Password OTP Auth** - Verify user forgot-password through OTP SMTP Service
- **Consultations & Referrals** - intake, vitals (versioned), diagnoses, prescriptions, consultation finalization, outward referrals with status tracking, and printable DOH-style referral forms.
- **Maternal Care** - prenatal profile & pregnancy tracking, prenatal visit records, postpartum/postnatal visits, obstetric history, and family planning client management with visit logging.
- **Immunizations** - child & adult immunization schedules, vaccine administration, marked-as-done tracking, no-show handling, and infant enrollment with printable Immunization Cards Export.
- **Households & Patients** - zone-based household registry, patient records, shared-searchable records, encryption of sensitive PHI.
- **Reports** - morbidity, MCH-EPI-FP Reports with PDF downloads and CSV export for households.
- **Administration** - user/role management, data-driven permissions, application settings, notifications, audit-logged actions.
- **ICD-10 Diagnosis Lookup** - Standard ICD-10 Diagnosis Lookup using WHO ICD-10 Version:2019 (Latest) latest and appropriate in BHC reporting
- **DOH-style Print Handouts & PDF Forms** - black 1px border, fixed-grid print layouts generated via Spatie Laravel PDF + Browsershot/DOMPDF.

## Tech Stack

| Layer         | Technology                                                                                                             |
| ------------- | ---------------------------------------------------------------------------------------------------------------------- |
| Backend       | [Laravel 12](https://laravel.com), PHP 8.2+                                                                            |
| Frontend      | [Blade](https://laravel.com/docs/blade), [Tailwind CSS v4](https://tailwindcss.com), Vite, Alpine-style Sweetalert2 UI |
| Frontend libs | ApexCharts, Font Awesome 7, SweetAlert2                                                                                |
| PDFs          | `spatie/laravel-pdf` + `browsershot`/puppeteer, `barryvdh/laravel-dompdf`                                              |
| Database      | MySQL (SQLite for tests)                                                                                               |
| Tooling       | Laravel Pint, Larastan/PHPStan, PHPUnit, Laravel Sail, Laravel Pail                                                    |

## Getting Started

### Prerequisites

- PHP **8.2+**
- [Composer](https://getcomposer.org)
- [Node.js](https://nodejs.org) 18+ (with npm)
- A MySQL database (or SQLite for local testing)
- Chromium for puppeteer-based PDF rendering (installed automatically by `npm install`)

**Required PHP extensions:**

- `pdo_mysql` (MySQL driver)
- `mbstring`
- `openssl`
- `curl`
- `xml`
- `bcmath`
- `gd`
- `zip`

On **Ubuntu/WSL**, install them with:

```sh
sudo apt install php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-gd php8.2-zip
```

On **Windows**, enable them in `php.ini` by removing the `;` before each `extension=...` line.

### Installation

```sh
# 1. Clone the repository
git clone https://github.com/kyowshi-dev/bhccr-capstone-main.git
cd bhccr-capstone-main

# 2. Install everything (dependencies, .env, key, migrate, seed, build assets)
composer run setup
```

The `setup` script runs these steps automatically:

1. `composer install`
2. Copies `.env.example` to `.env` (if not present)
3. Generates `APP_KEY`
4. Runs database migrations
5. Seeds roles, permissions, users, and lookup data
6. Creates storage symlink
7. `npm install` + `npm run build`

After setup, your app is ready to run.

### Configuration

The `.env` file is created from `.env.example` during setup. Review these values for your environment:

| Variable | When to change |
| -------- | -------------- |
| `DB_PASSWORD` | Set your MySQL root password (empty by default) |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | Required only if you want OTP email auth. Use a Gmail App Password ([guide](https://myaccount.google.com/apppasswords)). |
| `BHCIS_ICD_API_*` | Optional. Enable + add WHO API credentials for live ICD-10 lookup. Falls back to local `diagnosis_lookup` table when disabled. |
| `SESSION_SECURE_COOKIE` | Set to `true` when serving over HTTPS. |
| `APP_DEBUG` | Keep `false`. Never enable in production. |

### Default Accounts

After seeding, these accounts are available (password: `password123` for all):

| Role    | Email                |
| ------- | -------------------- |
| Admin   | admin@bhcis.com      |
| Nurse   | nurse@bhcis.com      |
| Doctor  | doctor@bhcis.com     |
| Midwife | midwife2@bhcis.com   |
| BHW     | bhw1@bhcis.com       |

> **Warning:** Change all passwords before any non-local deployment.

## Windows Setup

The `composer run dev` script works on Windows. However, if you prefer to run services individually:

```sh
# Terminal 1 - App server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev

# Terminal 3 (optional) - Queue worker
php artisan queue:listen --tries=1 --timeout=0
```

If you encounter path issues, **WSL2** is recommended for the smoothest experience.

## Development

```sh
composer run dev
```

Runs `php artisan serve`, the queue listener, Laravel Pail logs, and Vite dev server together via `concurrently`.

## Scripts

| Command                    | Description                                                        |
| -------------------------- | ------------------------------------------------------------------ |
| `composer run dev`         | Serve app + queue + logs + Vite in parallel                        |
| `composer run setup`       | Full setup: install, .env, key, migrate, seed, storage, npm build  |
| `composer run test`        | `config:clear` + `php artisan test`                                |
| `composer run phpstan`     | PHPStan static analysis (Larastan)                                 |
| `vendor/bin/pint --dirty`  | Laravel Pint code style fixer                                      |
| `npm run build`            | Build production frontend assets (required after Blade/JS changes) |

## Project Structure

```
app/
├── Http/Controllers/        # Auth, Dashboard, Household, Patient, Consultation,
│                            # Referral, Immunization, Prenatal, Postnatal,
│                            # FamilyPlanning, Report, Prescription, User & Role mgmt...
├── Http/Requests/           # Form Request validation classes
├── Models/                  # Eloquent models (Patient, Pregnancy, Vaccination...)
├── Services/PdfService.php  # Shared PDF rendering (Spatie PDF + Browsershot)
└── Helpers/                 # Global helpers (user(), breadcrumbs)
routes/web.php               # All web routes (auth + protected)
resources/views/             # App-shell layouts + feature views
```

## Testing

```sh
composer run test                    # full suite (in-memory SQLite)
php artisan test --compact --filter=testName   # single test
```

Tests always use **in-memory SQLite** regardless of your `.env` database config (enforced in `phpunit.xml`). No database setup is required to run tests.

## Troubleshooting

**Puppeteer/Chromium download fails during `npm install`:**

Puppeteer downloads Chromium (~170MB). If it fails behind a proxy or restricted network:

1. Set `PUPPETEER_SKIP_DOWNLOAD=true` in your `.env`
2. Install Chrome or Chromium manually
3. Set `BROWSSHOT_CHROME_PATH` in `.env` to the Chrome executable path (e.g. `C:\Program Files\Google\Chrome\Application\chrome.exe`)

Without a working Chromium setup, the app runs but DOH print forms and referral sheets will not render PDFs.

**`composer run dev` fails on Windows CMD/PowerShell:**

Run the services individually (see [Windows Setup](#windows-setup)) or use WSL2.

**Migration errors on fresh install:**

Make sure your MySQL database exists and the `DB_*` variables in `.env` are correct. The database must be created before running `composer run setup`:

```sql
CREATE DATABASE bhcis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

See the [open issues](https://github.com/kyowshi-dev/bhccr-capstone-main/issues) for the full list of proposed features and known issues.

## License

Distributed under the MIT license. This is a **capstone project and is not affiliated with, or endorsed by, the Philippine DOH (Department of Health).**
