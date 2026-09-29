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

## Verification gates

Run before finalizing changes; exact commands live in `docs/ENGINEERING.md`:

- After PHP changes: test suite (PHPUnit; convert any Pest tests), then `vendor/bin/pint --dirty`, then PHPStan once as a final gate.
- After blade/JS changes: `npm run build` (a ViteException means run it).
- Changing security headers/CSP can silently break Alpine.js interactivity - verify in a real browser, not just HTTP status (`docs/ENGINEERING.md`).

## Architecture (high level)

- Controllers stay thin; reusable and domain logic lives in `app/Services/`; helpers live in `app/Helpers/`
- Permissions are data-driven (`Permission` model + `hasPermission()`): gate UI as defense in depth, enforce on the server (`permission:*` middleware plus `throttle:` on sensitive routes)
- Referrals use the `OutwardReferral` model (own table), not legacy referral columns
- Detailed backend conventions (PDF, ICD, notifications, session auth, CSP implementation, commands, tooling): `docs/ENGINEERING.md`

## On-demand skills

- Load `security-review` skill before auth/input/API/payment/sensitive-feature changes; `coding-standards` and `tdd-workflow` for new code. These are invoked on demand, not always-loaded.

## Docs

- `.opencode/skills/bhccr-ui-style/SKILL.md` - authoritative UI/UX and design-system reference; read before touching any view
- `docs/ENGINEERING.md` - commands, detailed backend architecture, CSP/security-header implementation, tooling
- `docs/AGENTS.md` - Laravel Boost guidelines (uses `artisan boost:mcp` MCP server per `.mcp.json`)
- `docs/database_and_routes.md` - DB schema + routes inventory
- `docs/security/` - security audit and hardening notes; `docs/CLAUDE.md`, `docs/REFACTOR_*` for related guidance

Don't create docs files unless asked.
