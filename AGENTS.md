Laravel 12 app: **BHCIS System Sta. Ana** - Barangay Health Center Information System (maternal tracking, child & adult immunization, consultations & referrals, other barangay services; college capstone, DOH-aligned but not affiliated with DOH). PHP 8.2+, Blade + Tailwind v4, session-based auth (custom `AuthController`, no Breeze/Jetstream/Filament).

Intended users: Barangay Sta. Ana health workers - BHWs, midwives, nurses, doctors, and admins.
The system complements their paper-log workflow; it does not replace official DOH processes.

## DOH and iClinicSys terminology

Keep these claims distinct. Never infer one from another:

- **DOH-aligned design/workflow** - visual and procedural alignment with government health forms and practice.
- **Visual/form modeling** - print forms reproduce iClinicSys/DOH layouts (e.g. `_doh-header`, ITR, patient-enrollment).
- **Report-format compatibility** - outputs shaped to match required report formats (e.g. FHSIS-style reports).
- **Data export/manual transfer** - records moved out by export or manual process.
- **Interoperability / actual system or API integration** - automated data exchange with external systems.
- **Official certification, approval, or compliance** - an authoritative endorsement.

MUST NOT describe BHCIS as DOH-certified, DOH-approved, officially integrated, interoperable, or government-affiliated unless project evidence explicitly establishes that claim.

## Instruction hierarchy

- **MUST** - violation is a defect or a safety/security concern.
- **SHOULD** - default project convention; deviate only with a valid reason.
- **MAY/PREFER** - implementation or design preference.

Aesthetic preferences never carry the same authority as security, privacy, accessibility, or clinical requirements.

## Non-negotiable requirements

- Patient/clinical safety: never present clinical status misleadingly (status is never color alone; errors are never green).
- Privacy/security: protect patient data; never expose secrets in code, logs, or client output.
- Server-side authorization: every protected action MUST be enforced on the server. UI permission state (hidden/disabled controls) is not a security boundary.
- Validation/data integrity: validation lives in `app/Http/Requests/` Form Request classes, not inline in controllers.
- Auditability: keep audit logging on important clinical writes (`AuditLog`).
- Accessibility-critical behavior: keyboard operability, visible focus, adequate contrast, labeled inputs, respect for `prefers-reduced-motion`.
- Official print/form requirements: DOH/iClinicSys print forms keep their fixed layouts, borders, and wording (see SKILL.md).
- Preserve existing functionality unless the requested change requires otherwise.

## UX principles

- Clinical users first: dense but scannable screens, no startup/SaaS decoration, never-ambiguous errors.
- Direction over decoration: a color, badge, or motion earns its place only if it helps the user.
- Task-oriented screens must make the primary task or next relevant action clear. Informational screens must make context and hierarchy clear.
- App-shell UI and official print/PDF forms are two distinct modes and must not share styling tokens.

## Change discipline

- Inspect the existing implementation before creating new components, helpers, services, tokens, or patterns.
- Reuse existing project patterns where appropriate.
- Make the smallest reasonable change that satisfies the request.
- Do not modify unrelated backend behavior, clinical calculations, permissions, database behavior, or workflows unless the task requires it.

## Commands

- `composer run dev` - runs `php artisan serve`, `queue:listen`, `pail`, and `npm run dev` together via concurrently
- `composer run test` - `config:clear` + `php artisan test` (PHPUnit; do NOT use Pest, convert any Pest tests)
- Single test: `php artisan test --compact --filter=testName`
- `vendor/bin/pint --dirty` before finalizing any PHP changes (never use `--test`)
- `composer run phpstan` (or `vendor/bin/phpstan analyse --no-progress --memory-limit=1G`) once as a final verification gate after PHP changes - same pattern as running tests; do NOT iterate on it interactively
- `npm run build` - required after blade/js changes; ViteException → run this or `composer run dev`
- `composer setup` - full fresh setup (composer install, .env, key, migrate, npm build)

## Architecture notes

- Global helpers autoloaded from `app/Helpers/helpers.php` (`user()`) and `app/Helpers/BreadcrumbHelper.php`; other domain helpers live in `app/Helpers/`
- Services for cross-cutting/domain logic live in `app/Services/` (`PdfService`, `IcdApiService`, `ReferralService`, `VitalsService`); keep controllers thin and move reusable logic into services
- PDFs go through `app/Services/PdfService.php` (spatie/laravel-pdf + browsershot/puppeteer - needs node_modules installed)
- ICD diagnosis lookup: remote WHO ICD API via `BHCIS_ICD_API_*` env vars; falls back to local `diagnosis_lookup` table when disabled
- Roles/permissions are data-driven (`Permission` model + `hasPermission()` helper); permission-gate nav items and actions in the UI as defense in depth, and enforce on the server (`permission:*` middleware plus `throttle:` on sensitive routes)
- Session auth: routes requiring login sit inside `Route::middleware('auth')->group(...)`; password-reset flows stay consistent with session auth
- Clinical writes: keep `AuditLog` logging; consultations carry a status enum (incl. `in_progress`) and `notified_at` for due/referral notifications
- Referrals: use the `OutwardReferral` model (own table), not legacy referral columns
- Notifications are DB-backed (`notifications` table, `NotificationController`)

## Security headers / CSP rules

- Alpine.js 3 evaluates `x-data`/`x-on` expressions via `new Function`, so `script-src` MUST include `'unsafe-eval'` (plus `'unsafe-inline'` for inline handlers) or every interactive page silently breaks - this happened once: adding a CSP without `unsafe-eval` crashed Alpine on the dashboard
- `layouts/bare.blade.php` and `consultations/handout.blade.php` load Alpine from the jsDelivr CDN, so `script-src` MUST also allow `https:` or those pages lose Alpine (handout sheet toggles) - check all Alpine sources (bundled via Vite vs CDN) when changing `script-src`
- After adding/changing security headers, verify with a real browser check (console + JS behavior) on an interactive page (dashboard) and a CDN-Alpine page (handout), not just HTTP status tests
- When removing a feature that shipped frontend assets (e.g. charts), delete its JS imports/assets too - orphaned `resources/js/charts.js` imports a stale vendor path and stale cached pages can produce Livewire `MethodNotFoundException` 500s (e.g. `toJSON`) until a hard refresh

## On-demand skills

- Load `security-review` skill before auth/input/API/payment/sensitive-feature changes; `coding-standards` and `tdd-workflow` for new code. These are invoked on demand, not always-loaded.

## Docs

- `.opencode/skills/bhccr-ui-style/SKILL.md` - authoritative UI/UX and design-system reference; read before touching any view
- `docs/AGENTS.md` - Laravel Boost guidelines (uses `artisan boost:mcp` MCP server per `.mcp.json`)
- `docs/database_and_routes.md` - DB schema + routes inventory
- `docs/security/` - security audit and hardening notes; `docs/CLAUDE.md`, `docs/REFACTOR_*` for related guidance

## Tooling

- `cloudflared` (Cloudflare Tunnel) is installed at `~/.local/bin/cloudflared` and on PATH. If missing/corrupt, reinstall: `curl -sL https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -o ~/.local/bin/cloudflared && chmod +x ~/.local/bin/cloudflared`. Verify with `cloudflared --version` (downloads smaller than ~40MB are truncated - re-download).

Tests use in-memory sqlite (`phpunit.xml`). Don't create docs files unless asked.
