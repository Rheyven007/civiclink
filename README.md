# CivicLink
**Inclusive Urban Governance and Participation System**

A centralized web-based platform connecting citizens, LGUs, and community organizations for participatory governance — aligned with SDG 9, 10, 11, and 16.

## Tech Stack
- PHP 8+ (procedural, mysqli) — no framework required
- MySQL / MariaDB
- Flat, dependency-free HTML/CSS (no JS frameworks) — fast-loading, accessible UI

## Setup

1. **Create the database**
   ```bash
   mysql -u root -p < database/civicbridge.sql
   ```
   This creates the `civicbridge` database, all tables, seed sectors, and a default admin account.

2. **Configure the connection**
   Edit `config/db.php` with your MySQL credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'civicbridge');
   ```

3. **Set folder permissions**
   ```bash
   chmod 755 uploads/
   ```

4. **Serve the app**
   - Place the `civicbridge/` folder in your web server root (XAMPP `htdocs`, Laragon `www`, etc.), or
   - Run PHP's built-in server for local testing:
     ```bash
     php -S localhost:8000
     ```

5. **Log in**
   - Default admin: `admin@civicbridge.gov` / `password`
   - **Change this password immediately** via My Profile after first login.
   - New citizens can self-register at `/auth/register.php`.

## Roles
| Role | Access |
|---|---|
| **Citizen** | Submit proposals, service requests, complaints; vote; join consultations; rate services |
| **Sector Representative** | All citizen features + sector issue visibility + proposal endorsement |
| **LGU Officer** | Case management (proposals/requests/complaints), mandatory justified decisions, SLA dashboard |
| **Administrator** | User & sector management, consultation moderation, analytics, full audit log |

## Module Map
```
/auth        login, register, logout
/citizen     dashboard, proposals, requests, complaints, consultations, feedback, notifications, profile
/sector      dashboard, sector issues, endorsements
/lgu         dashboard, unified case management, SLA/performance
/admin       dashboard, users, sectors, moderation (consultations), reports/analytics, audit logs
/config      db.php (database connection)
/includes    functions.php (helpers), header.php / footer.php (shared layout)
/database    civicbridge.sql (full schema + seed data)
/assets/css  style.css (flat design system)
```

## Design Notes
- **Modern civic UI**: green design system with soft shadows, focus rings, sticky topbar, and responsive layout.
- **Mobile-first navigation**: slide-out sidebar on small screens; landing page has a collapsible menu.
- **Notification panel**: recent alerts drop down from the topbar (full list still at Notifications).
- **Transparency UX**: decision logs use a visual timeline; proposal status shows a progress stepper.
- **Every LGU decision requires a written justification**, stored in `decision_logs` and shown to citizens — the accountability mechanism (SDG 16).
- **Sector tagging is voluntary** and used only for equity analytics, never enforced or exposed publicly per-citizen.
- All forms are server-validated; passwords are hashed with bcrypt; SQL uses prepared statements throughout.
- Minimal JS (vanilla) for menu + notification panel only — no frameworks.


## UI/UX (Nielsen Heuristics)
This release applies the 10 usability heuristics for civic participation:
- **Visibility of system status** — status badges, progress steppers, reference numbers (PROP-/REQ-/CMP-YYYY-#####), success screens, notification counts, “Needs your attention”
- **Match with the real world** — plain-language labels (“Report a Concern”, “Request a Public Service”), helper text, local civic terminology
- **User control** — Cancel + unsaved-warning, breadcrumbs, confirm before logout
- **Consistency** — shared badges, icons, button hierarchy, form patterns
- **Error prevention** — min lengths, disabled submit while processing, confirmation on destructive actions
- **Recognition over recall** — clickable dashboard stats, status help text, filters
- **Flexibility** — Quick Actions, filters, search-ready structure
- **Aesthetic & minimalist** — reduced clutter, clear hierarchy, empty states with CTAs
- **Error recovery** — field-level messages, non-technical errors
- **Help & documentation** — Help Center under citizen/sector nav (`/citizen/help.php`)
