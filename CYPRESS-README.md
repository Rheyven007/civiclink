# CivicLink Cypress tests

57 end-to-end tests across 4 specs (Cypress 16).

| Spec | What it covers | Writes data? |
| --- | --- | --- |
| `01-auth.cy.js` | Redirect to login, login form, wrong password, Quick sign-in, every role's home page, sign out, registration validation | No |
| `02-citizen.cy.js` | Community Hub, all citizen pages, composer, proposal/request/complaint validation, department routing, legal checkbox, accessibility settings | No |
| `03-staff.cy.js` | Sector Hub & endorsements, LGU overview/cases/SLA/reports, all admin pages, role-based access ("Restricted") | No |
| `04-workflows.cy.js` | Citizen creates a proposal, request and complaint → sector rep endorses → LGU officer records decisions → citizen sees the justification; new resident registration | **Yes** |

## 1. Install

Copy `cypress/`, `cypress.config.js` and `package.json` into the CivicLink folder (replace the old ones), then:

```bash
npm install
```

## 2. Start CivicLink

- XAMPP: start Apache + MySQL, import the database, and keep `QUICK_LOGIN` on in `api/config.php`.
- Tell Cypress where the app is (default is `http://localhost:3000/`):

```bash
# Windows CMD
set CIVICLINK_URL=http://localhost/civiclink/
# PowerShell
$env:CIVICLINK_URL="http://localhost/civiclink/"
```

> If you serve it with PHP's built-in server, start it with several workers, otherwise
> the app's synchronous requests can freeze the test runner:
> `PHP_CLI_SERVER_WORKERS=4 php -S localhost:3000` (PowerShell: `$env:PHP_CLI_SERVER_WORKERS=4; php -S localhost:3000`)

No database? Run `npm run mock:api` to start an in-memory fake API on port 3000 (sample accounts, password `password`).

## 3. Run

```bash
npx cypress open                 # interactive
npm run test:e2e                 # headless, all specs
npm run test:e2e:readonly        # skip 04-workflows (creates no records)
npx cypress run --spec cypress/e2e/02-citizen.cy.js
```

Run `04-workflows.cy.js` against a **test copy** of the database: every run adds records (titles include a unique run ID).

## How it works

- `cy.loginAs('citizen' | 'sector' | 'lgu' | 'admin')` signs in through the API using the Quick sign-in accounts and caches the session with `cy.session` (fast and reliable). If QUICK_LOGIN is off, set `expose.accounts` in `cypress.config.js`.
- `cy.visitApp('#/route')` opens a page, skips the first-run tour and community reminder, and accepts the Terms of Use gate if it's shown.
- `cy.shouldShowPage('Title')` checks the page title and that no error, 404 or "Restricted" screen appeared.

## What was wrong with the old suite

- The **Terms of Use** popup covered the page, so clicks failed for accounts that hadn't accepted the current version.
- `cy.contains('Community Hub')` matched a hidden nav link instead of the page.
- A citizen opening `#/lgu/cases` stays on that URL and sees a "Restricted" screen, so the old "URL should change" check could never pass.
- Submission forms have a required **"I confirm this is true…"** (`legal_ok`) checkbox, which the old tests never checked.
- Cypress 16 removed `Cypress.env()`; this suite uses `Cypress.expose()`.
