# Education timeline: understand the implementation

## Request to response

1. `GET /education` is registered in `routes/web.php` and handled by
   `FrontendController::section()`, which calls `render('education')`.
2. `FrontendService::getAllData()` retrieves the existing CMS content through
   service interfaces. Academic records still come from the `education` table;
   this change does not migrate, seed or rewrite that data.
3. `FrontendController::render()` checks maintenance mode, the active theme and
   section visibility. Atlas can show education when either academic records,
   configured certificates or configured plans exist. Hiding education in the
   CMS still hides its navigation and redirects its page to the home anchor.
4. `resources/views/frontend/theme/atlas.blade.php` renders the page and includes
   `partials/education-timeline.blade.php`. The partial shows academic records,
   courses ordered by completion date, then a clearly separate set of plans.
5. `config/education.php` holds the curated public certificate metadata and
   aspirations. `public/certificates/` holds WebP previews rendered from the
   supplied PDFs. Laravel's `asset()` builds their public URLs; the web server
   serves the images directly. No new API, upload endpoint or database query is
   needed for certificate viewing.

## Why this design

The existing CMS remains responsible for academic records. A small versioned
configuration is sufficient for five verified course certificates and four
plans; it makes changes reviewable and deployable without production data edits.
An alternative is extending the CMS with certificate uploads and management.
That would be appropriate if frequent non-developer editing becomes necessary,
but would add schema, validation, storage and admin UI work now.

Native `<details>` and `<summary>` provide keyboard-operable inline viewing
without JavaScript or a viewer library. WebP previews work on mobile where
embedded PDF viewers can be inconsistent, and total roughly 335 KiB. Images
have intrinsic dimensions, lazy loading and descriptive alternative text; course
names, issuers and dates are also available as normal page text.
The images are presentation assets, not downloadable attachments in the UI.
They are not DRM: visitors can save any publicly displayed image. Original
PDFs and the source ZIP are not published.

The AWS documents are course-completion evidence, not AWS certification exam
passes. SymfonyCasts' PHP Namespaces course is not a Symfony certification.
Only SymfonyCasts supplies a verification URL in the provided documents; the
AWS entries therefore do not invent verification links. No AWS certification
level is assumed for the future Solutions Architect goal. Wits honours is an
aspiration, not an admission or enrolment claim. The unavailable Y Combinator
credential is omitted until supporting evidence or explicit public wording is
provided.

## Failure modes and safety

- A missing preview produces a broken image, even if the course text renders.
  The feature test checks every referenced asset and its dimensions.
- Incorrect metadata can misrepresent a qualification: check titles and dates
  against the source before editing. Planned entries have no completion dates.
- Config and Blade changes require deployment; stale Laravel caches can hide
  changes. Use the existing deployment/cache lifecycle rather than changing
  production rows or resetting the database.
- Blade escapes the new content with `{{ }}`; certificate paths and verification
  URLs are reviewed repository data, not untrusted visitor input. If uploads
  are added later, file type/size, path and URL validation become required.
- These additions apply to Atlas. Other themes retain their existing one-page
  education behaviour; maintenance mode and the section visibility flag still
  take precedence.

## Verify it yourself

From the repository root:

```bash
./vendor/bin/phpunit tests/Feature/AtlasProjectsPageTest.php
bash bin/validate.sh
```

The focused test should pass with five inline viewers, chronological course
ordering, four clearly labelled plans, valid image dimensions, no PDF/download
links and working section visibility. It also checks the education page with no
CMS academic rows and the redirect when all education sources are empty.
The repository validator additionally runs the full PHP suite, ESLint, a Vite
production build and Compose configuration checks. Neither command proves a
production deployment or manual accessibility by itself.

In a browser, visit `/education`, press Tab to a "View certificate" summary,
press Enter or Space and verify that the certificate opens in place. Repeat at
a narrow mobile width and confirm that the page does not scroll horizontally.
Check the issuer verification link separately; its availability belongs to the
issuer, not this application.

## Learning checkpoint

Before changing anything, predict what happens if the CMS education array is
empty but certificates remain configured. Then read the `$hasContent` condition
in `FrontendController` and `$hasEducation` in the Atlas view, and run the focused
test to check your prediction. Why must both navigation and route visibility
use the same rule?

For a small local exercise, change one planned goal's description in
`config/education.php`, reload `/education` and explain why no database migration
was needed. Revert that experiment before publishing. The next deeper exercise
is to trace an existing CMS education record from admin request validation
through its service and Eloquent model to the rendered page.
