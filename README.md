# CivicLink — Vanilla JS Redesign

Inclusive Urban Governance and Participation System, rebuilt as a single-page app in **plain HTML, CSS and JavaScript** (no frameworks, no build step, no CDN).

## Setup with MySQL (XAMPP / Laragon)
1. Copy the `civiclink-js` folder to `C:\xampp\htdocs\civiclink` (any folder name works).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **phpMyAdmin** → **Import**, then import these files in this order:
   1. `database/civiclink_schema.sql` creates the `civiclink` database and all tables. **It drops existing tables.**
   2. `database/civiclink_seed.sql` loads the sample accounts and data. This step is optional.
   - **Already have a `civiclink` database (e.g. your `civiclink.sql`)?** Skip step 1 and run `database/migrate_v4.sql` on it. It only adds the two new v4 tables (`terms_acceptances`, `user_preferences`) and keeps your data. (Databases older than v2 need `migrate_v1_to_v2.sql` first.)
   - `database/civiclink_full_v4.sql` is your uploaded database with the v4 migration already appended, ready to import.
4. Edit `api/config.php` if your MySQL user, password, or database name is different (XAMPP default: `root`, no password).
5. Open **http://localhost/civiclink/**. The login screen shows **Quick sign-in** buttons, one per role, read from the `users` table.

No XAMPP? From the project folder, run `php -S localhost:8000` and open http://localhost:8000.

Requirements: PHP 8.0+ with `pdo_mysql`, plus MySQL 5.7+ or MariaDB 10.3+. The `uploads/` folder must be writable.

### No demo mode
The browser-only demo mode was removed in v4. CivicLink always uses the PHP API and MySQL/MariaDB. If the API or database can't be reached, the app shows a **connection screen** with the error and a *Try again* button.

### Quick sign-in
`api/config.php` → `QUICK_LOGIN` (default `true`) lists one active account per role on the login page; clicking one fills the real sign-in form and submits it. `QUICK_LOGIN_PASSWORD` is the shared password of the sample accounts (`password`). **Set `QUICK_LOGIN` to `false` before going live.**

## How it works
- `api/index.php` is a JSON API using PDO prepared statements. It handles these actions:
  - `bootstrap`, `login`, `logout`, `register`, `password`
  - `insert`, `insert_many`, `update`, `delete`
  - `setting`
- `js/db.js` loads the user's data once after login and keeps it in memory. Every change is sent to the API, and the app refreshes every 30 s so users see each other's updates.
- Security is enforced on the server:
  - Passwords use `password_hash` / `password_verify`, with brute-force throttling.
  - The PHP session is regenerated on login.
  - POST requests need a custom `X-CivicLink` header (CSRF guard).
  - Ownership is forced on the server: `user_id`, `decided_by`, and the initial status are set by PHP.
  - Role checks apply to every table. Only LGU officers can record decisions; only admins can manage users, sectors, consultations, and settings.
  - Each role only receives the data it may see. Citizens get only their own requests, complaints, feedback, and notifications; other users' emails and IDs are never sent.
- Uploads are decoded, checked by real file type (JPG/PNG/WEBP/PDF, max 5 MB), and saved to `uploads/` with random names. Script execution in `uploads/` is blocked by `.htaccess`.

## v4.1 — Database-only, quick sign-in, motion & mobile
- **Demo mode removed** (`js/db.js` is API-only). Admin *Reset demo data* removed. A connection screen appears when the API/database is down.
- **Quick sign-in** from the database (`QUICK_LOGIN` in `api/config.php`).
- **Report post removed** (proposal menu, form and handler).
- **Database:** new `terms_acceptances` and `user_preferences` tables (`database/migrate_v4.sql`; also in `civiclink_schema.sql`). New API action `pref`; bootstrap returns `prefs`, `terms_acceptances` and `quick_login`. The API still works on databases without the new tables (it falls back gracefully).
- **Motion:** page-entry stagger, bars that fill in, counter pop, button press feedback, modal scale/slide with exit animation, theme cross-fade, drawer stagger. All motion is off with *Reduce motion* or the OS setting.
- **Mobile:** tab bar hides while scrolling down, swipe-down to close bottom sheets, sticky sheet actions, tables become cards, swipeable status board, 16 px inputs (no iOS zoom), 44 px touch targets.
- `database/make_seed.js` was removed (it generated SQL from the old browser demo data).

## v4 — Civic design system (UI/UX redesign)
The interface was rebuilt as a civic-collaboration platform. **Logic is unchanged:** every route, `data-act`/`data-form` handler, DB/API call, role permission and validation rule from v3 still works the same way. `js/db.js`, `js/logic.js`, `api/` and `database/` were not touched.
- **Brand:** a new "link" mark (a resident arc joining the community and the local government node), a deep civic teal (`#0B6463`) and a civic gold accent. Noto Sans with tabular figures for reference numbers.
- **Semantic colors:** `--community`, `--gov`, `--emergency`, `--info`, `--success`, `--warning`. Text and background pairs meet WCAG AA in light, dark and high-contrast modes.
- **Shell:** an official civic strip (LGU notice, laws, 911); a header with community context, global search, *Report a Concern*, accessibility, help, notifications and profile; activity-based context navigation (Community / Services / My Activity, or Operations / Administration for staff); a civic alert bar built from real data (an open consultation or overdue SLA cases); and a mobile tab bar with a central *Report* action and a drawer.
- **Community Activity Hub:** a greeting with quick actions, a community status board, the *Community ↔ Government* workflow, a civic bulletin, and typed **Civic Activity Cards** (Community proposal, Public consultation, Official notice) with stage progress, support level, endorsements, positions (Support/Oppose) and structured discussion.
- **Official Civic Notice:** LGU decisions appear as formal notices with the office band, the decision, facts and a quoted justification.
- **Cases:** a case list showing stage progress, and a case sheet with facts, an SLA meter, a vertical **progress timeline** and a *Resident ↔ Government* hand-off chain.
- **Notifications** are grouped into Government, Community, Consultations and Cases.
- **Civic profile:** identity, community, verification and a civic activity record.
- **Accessibility settings** (header button or account menu; off by default, saved in `civiclink.a11y`): text size, theme, high contrast, reduced motion, underlined links and readable spacing.
- **Components:** primary/secondary/tertiary/destructive buttons; accessible modals (focus trap, focus restore, Esc, labelled title, bottom sheet on phones); skeleton loading; empty states with a next action; 150–250 ms transitions; a skip link and visible focus rings.

## v3 — Social UI, laws & terms, guided tour, reports
- **Social-media layout:** top navigation with icon tabs, left shortcuts, story-style Highlights, post reactions summary, inline comments, Share, a post menu, a bottom tab bar with a center Create button on mobile, and a floating **Help** button.
- **Laws & Terms** (`js/legal.js`, route `#/legal`, also available signed out): Terms of Use, a Privacy Notice (RA 10173), and 15 supported Philippine laws, including RA 10175, RA 11313, RA 7160, Katarungang Pambarangay, RA 11032, RA 6713, EO 2 s.2016, RA 8792, RA 11055, and the sector laws.
  - **Reminders:** Users must accept the terms on first sign-in and again whenever `Legal.VERSION` changes. A community reminder appears once per session, and every form shows a notice with the relevant laws plus a required confirmation checkbox. Registration requires consent.
- **Guided tour** (`js/tour.js`): Starts automatically for first-time users. It highlights features step by step, with separate steps for each role. Replay it from Help, which also has quick jumps and an FAQ.
- **Reports** (Admin + LGU officers): 7/30/90-day or all-time period, comparison with the previous period, sparklines, activity trend chart, auto-generated insights, proposal pipeline, participation by sector and barangay, SLA and resolution gauges, department scorecard, officer ranking, an **RA 11032** processing-time compliance table, legal statements, summary CSV, and a print/PDF layout.
- Terms acceptance is stored in the `terms_acceptances` table and the audit log. Tour completion, theme and accessibility settings are stored per user in `user_preferences`.

## Sample accounts (password: `password`)
| Role | Email |
|---|---|
| Citizen | citizen@civiclink.gov |
| Sector Representative (Youth) | sector@civiclink.gov |
| LGU Officer (Engineering) | officer@civiclink.gov |
| Administrator | admin@civiclink.gov |

The login screen has quick sign-in buttons for each role (see *Quick sign-in*). To reset the sample data in MySQL, re-import `civiclink_schema.sql` + `civiclink_seed.sql`.

## Structure
```
index.html
css/civiclink.css      CivicLink v4 design system (tokens, components, light/dark/high-contrast, responsive, print)
assets/logo.svg        brand mark (favicon-64.png / logo-sm.png are exports of it)
api/index.php          PHP JSON API (PDO, sessions, permissions, uploads)
api/config.php         database credentials
database/              schema, seed, migration from the old civiclink.sql
uploads/               uploaded photos, IDs and evidence
js/db.js               data layer — MySQL/MariaDB through the PHP API (no browser fallback)
js/ui.js               icons, badges, modal, toast, charts, CSV export
js/logic.js            business rules: status workflows, decisions, SLA, notifications, audit
js/app.js              hash router, app shell, event delegation
js/views-public.js     sign in / registration
js/views-citizen.js    feed, proposals, requests, complaints, consultations, notifications, feedback, profile, search
js/views-staff.js      sector hub, LGU case management + SLA, admin users/sectors/consultations/reports/audit/settings
```

## New tables vs. civiclink.sql
- `endorsements` (proposal_id, user_id, sector_id, statement, created_at) — sector endorsements are no longer stored as up-votes.
- `comments` (proposal_id, user_id, body, created_at) — public discussion on proposals.
- Extra columns: `decision_logs.from_status / to_status`, `service_requests.resolved_at / location / attachment / cancel_reason`, `consultation_responses.hidden`, `users.photo / bio / last_login`.
