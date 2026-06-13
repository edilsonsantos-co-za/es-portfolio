# EdFolio Upgrade Path

This document defines the phased upgrade path for EdFolio from the current Laravel 10 / React 18 baseline to a modern, supportable Laravel 12+ and Vite-based frontend stack.

The production database is active, so every phase must be reversible, observable, and safe for zero-downtime or near-zero-downtime deployment.

## Current Baseline

- Backend: Laravel `10.x`
- PHP: Composer constraint `^8.1`; Docker image uses `php:8.2-apache`
- Frontend: React `18`, Laravel Mix `6`, Webpack entrypoints in `webpack.mix.js`
- Node: Docker and CI currently use Node `18`; local Node `26` is not compatible with the current Laravel Mix/Webpack production build
- Database: production MySQL database is active and must not be destructively migrated in the same release as application code
- Deployment: image-based production deploys using Docker Hub release tags generated from the root `VERSION` file

## Non-Negotiable Production Safety Rules

1. **No big-bang upgrades**: Laravel `9 -> 10` is complete; continue incrementally (`10 -> 11 -> 12`) and deploy each major version separately.
2. **No destructive migrations in upgrade releases**: avoid dropping or renaming columns, tables, indexes, routes, or config until a later cleanup release proves the replacement path is stable.
3. **Use expand/contract database changes**:
   - expand with nullable columns, new tables, compatible indexes, and dual-write support;
   - backfill in batches;
   - switch reads after verification;
   - contract only in a later release.
4. **Build assets in CI, not on the VPS**: production should continue pulling immutable images with prebuilt assets.
5. **Pin runtime versions**: standardize Node and PHP versions in local development, Docker, and CI before framework upgrades.
6. **Every deploy must have rollback instructions**: roll back to a previous image tag and avoid irreversible migrations.
7. **Dangerous administrative routes must be removed or strongly gated before major upgrades**.

## Phase 0: Safeguards Before Upgrade Work

Complete this before changing framework or frontend dependencies.

### Application and Route Hardening

- Remove or strictly restrict dangerous routes:
  - `/command/{cmd}` must never be available in production and should be removed if not essential.
  - `/optimize` should be converted to a protected operational command or removed from public routing.
  - `/admin/system-logs` must require strong admin authorization and should not expose raw sensitive log data.
- Replace or strongly gate `rap2hpoutre/laravel-log-viewer`; prefer a private observability channel or authenticated, audited admin-only log access.
- Keep Laravel's built-in CORS middleware enabled and preserve `config/cors.php` behavior.
- Review JWT authentication:
  - keep `tymon/jwt-auth` only if stateless token behavior is still required;
  - prefer Laravel Sanctum for first-party admin authentication unless OAuth or third-party token clients are required.

### CI and Repository Safeguards

- Keep the existing validation path current and make it mandatory for pull requests.
- Add dependency and security checks:
  - Composer audit
  - npm audit or an equivalent frontend dependency audit
  - automated dependency updates with grouped framework/frontend PRs
- Add or enforce GitHub branch protection/rulesets:
  - require validation checks before merge;
  - require reviews for upgrade PRs;
  - prevent direct pushes to protected release branches.
- Add smoke tests for:
  - public home page;
  - admin login page;
  - health endpoint;
  - one representative API endpoint.
- Strengthen the health endpoint so deployment checks verify Laravel boot, database reachability, cache/session dependencies, and essential storage paths.

### Runtime Standardization

- Keep PHP aligned across Composer platform, Dockerfile, CI, and production.
- Standardize on a supported Node LTS for the current build before migrating tools.
- Do not use Node `26` with the current Laravel Mix/Webpack setup unless the production build has been proven locally and in CI.

## Phase 1: Backend Upgrade to Laravel 10 — Completed / Current

Goal: reach Laravel 10 with minimal product behavior changes.

Status: completed and current. Composer now targets Laravel `^10.0`, PHP `^8.1`, PHPUnit `^10.1`, Collision `^7`, and Ignition `^2`; `app/Http/Kernel.php` uses Laravel 10 `middlewareAliases`; `phpunit.xml` uses the PHPUnit 10 schema. Full validation passed after the upgrade.

Completed sequence:

1. Create an upgrade branch and capture a production backup before dependency changes.
2. Remove or replace abandoned packages that block Laravel 10.
3. Upgrade Composer constraints for Laravel 10-compatible packages.
4. Keep PHP on `8.2.x` initially unless dependencies require a newer minor.
5. Run and fix:
   - Composer install/update resolution;
   - PHPUnit;
   - route listing;
   - config/cache/view cache commands;
   - application smoke tests.
6. Deploy as its own release tag.
7. Monitor logs, queue behavior if applicable, admin login, contact form, and portfolio reads.

Laravel 10 should not include frontend modernization beyond fixes required to keep the existing Mix build working.

## Phase 2: Backend Upgrade to Laravel 11

Goal: modernize Laravel application structure carefully while preserving runtime behavior.

Special care areas:

- Kernel and middleware registration changed significantly in newer Laravel versions.
- Route service provider behavior must be reviewed before moving route configuration.
- Exception handling, scheduler, and bootstrap configuration should be refactored in small commits with tests.
- Keep existing route names, URL paths, and API responses stable unless a migration plan exists.

Recommended sequence:

1. Upgrade framework and first-party packages to Laravel 11-compatible versions.
2. Refactor `Kernel` and route provider setup only as required by Laravel 11.
3. Verify middleware ordering for web, API, auth, CORS, session, CSRF, throttling, and admin routes.
4. Re-run full backend validation and smoke tests.
5. Deploy independently from database and frontend rewrites.

## Phase 3: Backend Upgrade to Laravel 12

Goal: complete the supported Laravel target version without combining unrelated product changes.

Recommended sequence:

1. Upgrade Laravel `11 -> 12` and all required first-party packages.
2. Re-check authentication packages and decide whether `tymon/jwt-auth` remains justified.
3. If moving to Sanctum:
   - add Sanctum alongside JWT first;
   - dual-support authentication where needed;
   - migrate admin sessions/tokens;
   - remove JWT only after a later release proves Sanctum coverage.
4. Re-run security, route, config, and smoke validations.
5. Deploy as a tagged release and monitor before starting frontend modernization.

## Phase 4: Frontend Runtime and Build Tooling

Goal: move from Laravel Mix/Webpack to a modern frontend pipeline without changing React behavior first.

Recommended sequence:

1. Standardize Node LTS across local, Docker, and CI.
2. Keep the existing Mix build green before introducing Vite.
3. Introduce Vite with equivalent entrypoints to `webpack.mix.js`.
4. Confirm Blade asset references, manifests, cache-busting, production image builds, and local dev workflows.
5. Remove Laravel Mix only after Vite production builds are validated in CI and deployment images.

Validation:

- `npm run build`
- frontend linting
- admin page rendering
- public page rendering
- production image contains compiled assets and the expected manifest

## Phase 5: Frontend Library Modernization

Do this after Vite is stable.

Recommended order:

1. React Router `v5 -> v6/v7`
   - update route declarations and navigation;
   - verify admin deep links and redirects.
2. Redux to Redux Toolkit
   - migrate store slices incrementally;
   - keep API contracts stable.
3. styled-components `6`
   - verify server/client class generation assumptions and theming.
4. Ant Design `6`
   - migrate components and theme configuration in focused batches.
5. React `19`
   - upgrade last, after router, state, styling, and component-library compatibility is confirmed.

Avoid mixing React 19 with the build-tool migration in the same release.

## Phase 6: Database Modernization

Database work must follow zero-downtime migration patterns.

### Candidate Improvements

- Add unique constraints after data cleanup:
  - `admins.email`
  - `settings.setting_key`
  - `portfolio_configs.setting_key`
- Add or review indexes for common access patterns:
  - messages by read/status/date fields;
  - visitors by IP/date/session or analytics dimensions;
  - projects by visibility, ordering, category, slug, or featured status.
- Improve project categories and images:
  - migrate string/blob-style structures to native JSON columns where appropriate; or
  - normalize into dedicated category/image tables if querying and filtering require relational integrity.

### Safe Migration Pattern

1. Add new nullable columns/tables/indexes.
2. Deploy code that writes both old and new structures.
3. Backfill in small batches with progress logging and retry support.
4. Compare old and new reads in logs or admin-only diagnostics.
5. Switch reads to the new structure.
6. Keep old columns for at least one release.
7. Drop old columns/tables only after rollback no longer depends on them.

### Indexing Rules

- Use online index creation where supported by the database version.
- Check table size and lock behavior before applying indexes in production.
- Add indexes in separate migrations from application behavior changes when possible.

## Zero-Downtime Deployment Strategy

Preferred deployment model:

1. Build immutable image in CI.
2. Run pre-deploy checks:
   - release tag matches `VERSION`;
   - image exists and starts;
   - migrations are forward-compatible;
   - database backup completed.
3. Apply additive migrations.
4. Deploy with blue/green or rolling replacement where available.
5. Run smoke tests against the new container before switching traffic.
6. Keep the previous image tag available for rollback.

If true blue/green is not yet implemented, minimize downtime by keeping migrations additive and ensuring the previous image can run against the migrated schema.

## Validation Checklist Per Phase

Minimum validation before merge or deploy:

- Composer dependency resolution
- Composer audit
- PHPUnit
- Laravel config/cache/route validation
- npm install using the standardized Node LTS
- frontend lint
- production asset build
- Docker image build
- smoke tests for public, admin, API, and health routes
- migration dry-run or production-like migration check
- rollback rehearsal for high-risk phases

Document any validation that cannot be run locally before release approval.

## Rollback Strategy

Rollback must remain image-tag based.

1. Identify the last known-good release tag.
2. Confirm the database schema is still compatible with that previous image.
3. Pull and redeploy the previous image tag.
4. Clear Laravel config/cache if required.
5. Run smoke tests.
6. Monitor error rates and logs.

Never rely on rolling back destructive database migrations during incident response. If a migration might not be reversible quickly, redesign it as an expand/contract migration.

## Post-Upgrade Feature Ideas

After Laravel 12, Vite, and frontend library upgrades are stable, prioritize product improvements that benefit from the modernized stack:

- admin two-factor authentication and recovery codes;
- role-based admin permissions;
- audited admin activity log;
- richer media library with image variants and WebP/AVIF generation;
- project category filtering and case-study pages;
- improved visitor analytics with retention controls;
- SEO preview tooling and structured data management;
- API documentation with OpenAPI;
- content scheduling and blog/articles support;
- stronger backup/restore workflows surfaced in admin diagnostics.

These features should be planned only after the platform upgrade risk has been retired.
