# Admin: CV, mail, contact replies, courses and the dashboard (Nov 2026)

What changed on 17 Nov 2026, how each part works, and how to check it yourself.

## Generated CV (`GET /cv`)

`CvController::content()` builds the CV from the content already in the admin.
That is About (name, first tagline, address, email, profile, links), Experience
(newest first), Skills (strongest first), Education, courses completed and Projects.
`resources/views/frontend/cv.blade.php` lays it out, and dompdf
(`barryvdh/laravel-dompdf`) turns it into an A4 PDF on every request. Nothing is
stored, so editing the content changes the next download. No AI is involved.

- Each section follows its Visibility switch. The CV switch hides the whole CV (404).
- The phone number is left out because the public site never shows it. Plans are
  left out because they are goals, not qualifications.
- Experience HTML keeps only formatting tags (`p ul ol li strong em br`).
- The route is throttled to 20 requests a minute: each one renders a PDF.
- The old uploaded CV file is no longer linked. The `abouts.cv` column is kept.

Alternative not chosen: a print-styled HTML page that visitors save as PDF. It is
lighter, but each browser produces a different file, and the button would no
longer give a document.

## Mail sender

The connection (host, port, user, password) is server configuration and is shown
read-only. The password is no longer sent to the browser. The sender name and
address are saved as settings 20 and 21. `SettingService::applyMailSender()` runs
in `AppServiceProvider::boot()` and overrides `mail.from` on every request.

Failure modes:

- An address outside the sending domain is refused (400), because SES would
  reject it.
- A saved address that is not a valid email is ignored rather than breaking mail.
- Keys 8 and 9 were avoided because older databases hold legacy theme rows there
  (value `light`). Using them made every email fail during testing.

## Contact form and replies

`GeneralController::store` saves the message, then `ContactMail::received()` sends:

- a confirmation to the visitor;
- a notification to the owner, with Reply-To set to the visitor.

The site shows a toast with the result. Admin > Messages > Reply opens the drawer
with a reply box. `POST /admin/messages/{id}/reply` stores the reply in
`message_replies`, emails it with Reply-To set to the owner, and marks the message
replied. If sending fails, the reply row is removed and the admin sees an error.
Mail is sent inline (the queue is `sync`), so a slow mail server slows the request.

## Courses and plans

See `education-and-learning.md`.

## Dashboard picture and quote

`GET /admin/daily` returns Wikimedia Commons' Picture of the Day (no key; the
credit and licence are shown on the tile) and a quote from `config/daily_quotes.php`
chosen by the day of the year. The server caches the picture for the day. A failed
lookup is cached for an hour, and the tile falls back to its plain colour.

## Admin look

- Dropdowns: one size everywhere (15px text, 40px rows), set in the Ant Design
  theme in `roots/app.js`. Floating menus use the lighter card colour so they stand
  out from the card behind them.
- Themes page: three cards straight on the page background.
- The theme picker was removed from Config; Themes is the only place to change it.

## Verify

```bash
docker exec es-portfolio-dev ./vendor/bin/phpunit tests/Feature/GeneratedCvTest.php tests/Feature/AtlasProjectsPageTest.php
docker exec es-portfolio-dev sh -c 'curl -s -X POST http://localhost/api/v1/messages -H "Content-Type: application/json" -H "Accept: application/json" -d "{\"name\":\"T\",\"email\":\"t@example.test\",\"subject\":\"Hi\",\"body\":\"Hello\"}"'
```

Expected: both tests pass. The response says "a confirmation is on its way". Mailpit
shows two emails, "I received your message" and "New message: Hi". The tests do not
prove delivery to a real inbox; production goes through useSend and SES.

## Try it

Predict which section disappears from the CV when Skills is switched off in
Portfolio > Config > Visibility. Switch it off, open `/cv`, then switch it back on.
