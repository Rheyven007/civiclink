# CivicLink — Gap Analysis (Proposal document vs. PHP app)

Legend: ✅ was missing / incomplete in the PHP app → now implemented in the vanilla JS version.

## Critical (required by the proposal document)
| # | Proposal section | Gap in the PHP app | Fix in redesign |
|---|---|---|---|
| 1 | 8.5 "supports cancellation where applicable" | Citizens can't cancel a service request (only officers can set status). | ✅ Citizen can cancel while *Submitted*, with reason; logged in decision history. |
| 2 | 8.5 / 8.6 "Assigned LGU personnel" | `assigned_to` is never set anywhere — no assignment UI. | ✅ Assign officer + change priority; assignee and citizen notified. Auto-assigns the officer who acts first. |
| 3 | 8.6 "Resolution notes can also be recorded" | No form to write `resolution_notes`. | ✅ Resolution notes field on complaint decisions; required to resolve. |
| 4 | 8.4 "Endorse proposals on behalf of the sector" | Endorsement = an up-vote (`proposal_votes`), so it's indistinguishable from a normal vote and not tied to a sector. | ✅ Separate `endorsements` table with sector + public statement; shown on cards, detail page, and to LGU reviewers; can be withdrawn. |
| 5 | 8.4 Sector rep needs a sector | Admin-created sector reps have no `citizen_profiles` row → no sector. | ✅ Admin must assign a sector to sector reps. |
| 6 | 6 / 9 "Transparent status information" | Decision history is only shown for proposals; requests & complaints show no justifications to citizens. | ✅ Decision timeline (who, when, from → to, justification) on every proposal, request and complaint. |
| 7 | 12 Feedback "after receiving services or having cases addressed" | Feedback isn't linked to a case (`reference_id` always NULL) and can be given anytime. | ✅ Rate a specific resolved/closed case once; general feedback still available; assignee notified. |
| 8 | 7 / 8.7 Workflow integrity | Officers can jump to any status (e.g. Pending → Implemented). | ✅ Enforced transitions per type (e.g. Approved → Implemented only). Final states are locked. |
| 9 | 10 SLA "defined processing threshold" | Single hard-coded threshold; avg. time based on `updated_at` (changes on any edit). | ✅ Per-priority SLA targets set by admin; real `resolved_at`; compliance %, department & officer performance, per-case SLA meter. |
| 10 | 14 Sector Management "maintain sector information" | Sectors can only be added/deleted, not edited. | ✅ Edit sectors; deleting moves members to General Public. |
| 11 | 14 Consultation moderation | Admin can create/close only — no editing and no moderation of responses. | ✅ Edit, reopen, delete consultations; hide/restore/remove individual responses. |
| 12 | 14 Reports "Generate reports" | Statistics page only — no export or printable report. | ✅ Date-range filter, 9 charts, CSV export per dataset, print-ready report. |
| 13 | 14 Audit logs "Monitor actions" | Search only. | ✅ Filter by action + date range, CSV export; many more actions logged (assignments, moderation, verification, exports…). |
| 14 | 8.1 "User status management" + `citizen_profiles.verified/valid_id_path` | Valid ID upload and verification columns exist in DB but are never used. | ✅ ID upload at registration/profile; admin reviews and verifies; verified badge across the app. |

## Recommended additions (improve the system, not strictly in the document)
- ✅ Comments/discussion on proposals (supports "contribute to discussions", §7 Community Organizations).
- ✅ Global search (reference numbers like `REQ-0002`, keywords, people).
- ✅ Withdraw own pending proposal; vote toggle; can't vote on own proposal; voting closes after decision.
- ✅ Mark single notification read, unread filter (PHP marks everything read on page open).
- ✅ Attachments on service requests; evidence viewer for officers.
- ✅ Admin: reset password, notify users on role/status change, last login.
- ✅ Dark mode, mobile layout with bottom tab bar, accessible labels.

## Still outside scope / next steps
- Real backend: connect `js/db.js` to PHP + MySQL (see README). (Done in v4.1 — the browser demo mode was removed.)
- Email/SMS notifications, password reset via email, map pin for service requests.
