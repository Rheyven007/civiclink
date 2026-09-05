# CivicBridge
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
- **Flat design**: solid colors, no gradients/shadows, minimal CSS footprint for fast loads and low token/bandwidth cost.
- **Every LGU decision requires a written justification**, stored in `decision_logs` and shown to citizens on the proposal/request/complaint detail — this is the transparency and accountability mechanism (SDG 16).
- **Sector tagging is voluntary** and used only for equity analytics, never enforced or exposed publicly per-citizen.
- All forms are server-validated; passwords are hashed with bcrypt; SQL uses prepared statements throughout.
