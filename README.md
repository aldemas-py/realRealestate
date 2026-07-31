# Zahara Co-Working Space

**A Replacement of Traditional Workplace** — Premium office space booking and rental management system.

Located at **Krishna Centre, 2nd Floor, Westlands, Nairobi, Kenya**.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Business Model](#business-model)
3. [Technology Stack](#technology-stack)
4. [Architecture & Policy-as-Code](#architecture--policy-as-code)
5. [Compliance Framework](#compliance-framework)
6. [Database Schema](#database-schema)
7. [Installation & Setup](#installation--setup)
8. [User Flow](#user-flow)
9. [Admin Panel Features](#admin-panel-features)
10. [File Structure](#file-structure)
11. [API & Helper Functions](#api--helper-functions)
12. [Policy Rules Reference](#policy-rules-reference)
13. [Security & Compliance](#security--compliance)
14. [License](#license)

---

## Project Overview

Zahara Co-Working Space is a complete web-based platform for managing co-working office space rentals. The system enables:

- **Customers** to browse available spaces, request site visits, manage leases, and track payments.
- **Admins** to manage office spaces (CRUD + images), handle visit requests, create lease agreements, track rent payments, moderate testimonials, and approve user accounts.
- **Policy-as-Code enforcement** — all business rules are encoded in a configurable policy engine rather than hard-coded, making compliance audit-ready.

### Core Branding

| Element | Value |
|---------|-------|
| Company Name | Zahara Co-Working Space |
| Tagline | A Replacement of Traditional Workplace |
| Location | Krishna Centre, 2nd Floor, Westlands, Nairobi |
| Phone | 0724 161 342 |
| Email | info@zaharacowork.com |
| Currency | KES (Kenyan Shilling) |
| Primary Color | #1565C0 (Corporate Blue) |
| Secondary | #0D47A1 (Dark Blue) |

---

## Business Model

```
User browses website
        │
        ▼
Selects an available space
        │
        ▼
Requests a site visit (date & time)
        │
        ▼
Admin reviews & approves visit request
        │
        ▼
User visits the space (site visit completed)
        │
        ▼
User likes the space → Admin creates customer profile
        │
        ▼
Lease agreement drafted & signed by both parties
        │
        ▼
Customer pays security deposit
        │
        ▼
Lease activated → Space marked as occupied
        │
        ▼
Monthly rent payments tracked (admin records payments)
```

### Virtual Office Packages

| Package | Price (KES/mo) | Features |
|---------|---------------|----------|
| Business Presence | 3,000 | Business address, mail handling |
| Standard Virtual | 5,000 | Business Presence + 10hr meeting room access |
| Virtual Office Plus | 10,000 | Standard + dedicated virtual receptionist |

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 8.0 (Vanilla, no framework) — PDO for database access |
| **Database** | MariaDB 10.4 (MySQL-compatible) |
| **Frontend** | Custom CSS (responsive, mobile-first) |
| **JavaScript** | Vanilla JS + Leaflet.js (OpenStreetMap integration) |
| **Maps** | Leaflet.js 1.9.4 (free, no API key required) |
| **Server** | Apache 2.4.58 (XAMPP) |
| **Fonts** | Google Fonts — Inter |
| **Images** | Unsplash (placeholder) |

---

## Architecture & Policy-as-Code

### What is Policy-as-Code?

Policy-as-Code means business rules are written as **configurable policy records in the database** rather than being hard-coded in PHP logic. This allows:

- **Runtime configuration** — policies can be updated without code changes
- **Audit readiness** — every policy check can be logged
- **Compliance transparency** — all business rules are visible in one place
- **Enforcement consistency** — the same policy applies across all entry points

### Implementation

**Database Layer** — The `policy_rules` table stores 10 configurable policies:

```sql
policy_rules (
  policy_key,      -- Unique identifier (e.g., 'payment_grace_period_days')
  policy_name,     -- Human-readable name
  description,     -- What the policy enforces
  rule_type,       -- validation | workflow | access_control | notification | computation
  default_value,   -- The configurable value
  is_active        -- Enable/disable without deleting
)
```

**Application Layer** — A helper function reads policies:

```php
getPolicyValue($key, $default)  // Returns the active policy value
```

Policies are enforced in controllers before any state mutation occurs.

**Trigger Layer** — MySQL triggers enforce compliance at the database level:
- `after_user_insert` — Auto-logs new user registration to audit trail
- `after_lease_active` — Auto-updates space status to 'occupied' and logs activation
- Lease expiry/termination — Auto-frees the space back to 'available'

### Audit Trail

Every action that changes system state is recorded in `audit_log`:

```php
logAudit($userId, $action, $entityType, $entityId, $oldValues, $newValues, $complianceStatus);
```

This creates an immutable, timestamped record of:
- Who performed the action
- What action was performed
- What entity was affected
- Before and after snapshots (JSON)
- Compliance status (pass/fail/pending)
- Any policy violation descriptions

---

## Compliance Framework

### State Machines (Workflow Compliance)

Each business process has a defined state machine that governs valid transitions:

**Visit Requests:**
```
pending → approved → completed → lease_created
       → rejected (terminal)
       → cancelled (terminal)
```

**Leases:**
```
draft → deposit_pending → active → expiring → expired
                                → terminated (early)
```

**Payments:**
```
pending → paid
       → overdue (auto after grace period)
       → partially_paid
       → refunded
```

**User Accounts:**
```
pending → active (admin approval required)
       → suspended
       → inactive
```

### Enforced Policies (Compliance Rules)

| Policy Key | Rule Type | Default | Description |
|-----------|-----------|---------|-------------|
| `max_images_per_space` | validation | 10 | Max images per office space listing |
| `max_pending_visits` | validation | 3 | Max concurrent pending visit requests per user |
| `lease_min_duration_days` | validation | 30 | Minimum lease duration |
| `lease_max_duration_months` | validation | 24 | Maximum lease duration |
| `payment_grace_period_days` | computation | 5 | Days after due date before marked overdue |
| `late_fee_percentage` | computation | 5% | Percentage of rent charged as late fee |
| `deposit_percentage` | computation | 50% | Security deposit as % of monthly rent |
| `visit_request_expiry_days` | workflow | 7 | Days before pending visit auto-cancels |
| `lease_expiry_reminder_days` | notification | 30 | Days before expiry to send reminder |
| `rent_increase_notice_days` | notification | 30 | Days notice required for rent increase |

All policies can be managed live from the **Admin → Policy Engine** page at `/admin/policies.php`.

---

## Database Schema

The system uses **11 tables** with full foreign key constraints, triggers, and CHECK constraints.

### Entity Relationship Summary

```
roles ──→ users ──→ audit_log
  │         │
  │         ├──→ visit_requests ──→ office_spaces
  │         │                            │
  │         ├──→ leases ────────────────┤
  │         │      │                    │
  │         │      └──→ space_images ───┘
  │         │
  │         ├──→ payments ──→ leases
  │         │
  │         ├──→ testimonials ──→ office_spaces (optional)
  │         │
  │         └──→ notifications
  │
policy_rules (standalone, config-driven)

compliance_reports (generated reports)
```

### Key Tables

| Table | Purpose | Key Compliance Features |
|-------|---------|------------------------|
| `users` | User accounts with RBAC | Status workflow (pending→active→suspended), email format CHECK, FK to roles |
| `roles` | Role definitions | Admin (level 100), Customer (10), Visitor (0) |
| `audit_log` | Immutable action trail | JSON snapshots, compliance_status, policy_violation |
| `office_spaces` | Rentable spaces | Status machine (available→occupied→maintenance), JSON amenities/business_hours |
| `space_images` | Gallery per space | CASCADE delete, max enforced by policy |
| `visit_requests` | Site visit management | Full state machine, UNIQUE constraint prevents duplicates |
| `leases` | Rental agreements | Full state machine, date validation CHECK, deposit tracking, dual-signature |
| `payments` | Financial transactions | State machine, overdue tracking with late fee computation, UNIQUE prevents duplicates |
| `testimonials` | Customer reviews | Approval workflow, rating CHECK (1-5) |
| `notifications` | System alerts | policy_trigger field links to policy type |
| `policy_rules` | Policy-as-Code engine | 4 rule types, toggleable active state |
| `compliance_reports` | Generated audit reports | JSON parameters and summary |

---

## Installation & Setup

### Prerequisites

- XAMPP (or any Apache + PHP 8.0+ + MariaDB/MySQL environment)
- Web browser

### Quick Setup (1 minute)

1. **Clone or copy** the project to your XAMPP htdocs:
   ```
   C:\xampp\htdocs\work_folder\realRealestate\
   ```

2. **Start XAMPP** — ensure Apache and MySQL services are running.

3. **Visit the setup page** in your browser:
   ```
   http://127.0.0.1/work_folder/realRealestate/setup.php
   ```

4. **The setup script** will:
   - Create the `realestate_db` database
   - Run the schema (all 11 tables, constraints, triggers)
   - Seed sample data (5 office spaces, testimonials, policy rules)
   - Create the admin user with a known password

5. **Login** with default credentials:
   - **Admin:** info@zaharacowork.com / Admin@123
   - Access admin panel at `/admin/login.php`

### Manual Setup

If the automated setup fails, you can manually:

1. Import `sql/schema.sql` via phpMyAdmin
2. Import `sql/migration.sql` (if tables already exist)
3. Update `config/database.php` with your database credentials
4. Run the initial seed queries from the schema file

### Environment Configuration

Edit `config/database.php`:

```php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'realestate_db';
```

---

## User Flow

### Visitor (unauthenticated user)
1. Visit the landing page → view featured spaces and testimonials
2. Browse all available spaces with type filters (private_office, open_desk, meeting_room, virtual_office)
3. Click a space → view details, gallery images, and interactive map location
4. To request a visit → must register an account

### Customer (registered, pending approval)
1. Register account → status is 'pending'
2. Wait for admin approval
3. Once approved → can login and browse spaces
4. Request site visits → choose preferred date/time
5. Track visit request status in profile

### Customer (approved, with active lease)
1. View active lease details in profile
2. See payment history and upcoming due dates
3. Update personal information

### Admin
1. Login via admin panel
2. Dashboard shows key metrics (spaces, leases, payments, compliance alerts)
3. Manage all aspects of the system via sidebar navigation

---

## Admin Panel Features

| Section | Features |
|---------|----------|
| **Dashboard** | Stats: total/available/occupied spaces, active leases, active/pending customers, total revenue, pending visits, overdue payments, pending reviews, compliance status |
| **Spaces** | List all spaces, create new (name, type, capacity, price, location, amenities, images), edit, toggle status (available/maintenance), delete with audit |
| **Visit Requests** | View all requests, approve/reject with admin notes, mark as completed, linked to lease creation |
| **Customers** | View all customers, approve pending accounts, suspend active accounts, quick link to their leases |
| **Leases** | Create new lease (select customer + space + associated visit), set terms (start/end dates, rent, deposit, payment due day), activate (after deposit), terminate early |
| **Payments** | Record payments (rent, deposit, late fee, other), track due dates, mark overdue with automatic late fee calculation, view payment history |
| **Testimonials** | Moderation queue: approve/reject pending reviews, toggle featured status |
| **Users** | Full user table: approve pending, suspend/activate, view roles |
| **Policy Engine** | Live management of all Policy-as-Code rules — update values, toggle active/inactive |
| **Audit Log** | Full immutable compliance trail with pagination — view all actions, users, entities, JSON snapshots, and any policy violations |

---

## File Structure

```
realRealestate/
│
├── index.php                    # Landing page
├── setup.php                    # One-click database installer
├── TODO.md                      # Development checklist
├── README.md                    # This file
├── .gitignore
│
├── config/
│   └── database.php             # PDO database connection
│
├── includes/
│   ├── header.php               # HTML head, navigation, flash messages
│   ├── footer.php               # Footer, scripts, closing tags
│   └── auth.php                 # Session management, RBAC, helper functions
│
├── assets/
│   ├── css/
│   │   └── style.css            # Complete responsive CSS with custom properties
│   └── js/
│       ├── main.js              # Core JavaScript (gallery, dropdowns, tabs)
│       └── map.js               # Leaflet/OpenStreetMap integration
│
├── public/                      # Frontend (customer-facing) pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── profile.php              # User dashboard: leases, payments, visits, settings
│   ├── spaces.php               # Browse all spaces with filters & search
│   ├── space-detail.php         # Single space: gallery, map, visit request
│   ├── testimonials.php         # Reviews: view approved + submit new
│   ├── about.php                # Company information
│   └── contact.php              # Contact form + location map
│
├── admin/                       # Admin panel
│   ├── sidebar.php              # Navigation sidebar component
│   ├── login.php                # Separate admin login
│   ├── index.php                # Dashboard
│   ├── policies.php             # Policy Engine (Policy-as-Code management)
│   ├── audit.php                # Audit Log with pagination
│   ├── spaces/
│   │   ├── index.php            # List + delete + toggle status
│   │   ├── create.php           # Create form
│   │   └── edit.php             # Edit form
│   ├── visit-requests/
│   │   └── index.php            # Manage visit requests (approve/reject/complete)
│   ├── customers/
│   │   └── index.php            # Customer list + approve/suspend
│   ├── leases/
│   │   └── index.php            # Create leases + activate/terminate
│   ├── payments/
│   │   └── index.php            # Record payments + mark overdue
│   ├── testimonials/
│   │   └── index.php            # Moderate testimonials (approve/reject/feature)
│   └── users/
│       └── index.php            # Full user management
│
├── sql/
│   ├── schema.sql               # Complete database schema + triggers + seed data
│   └── migration.sql            # Incremental migration for existing databases
│
├── uploads/                     # (Optional) Uploaded space images directory
│   └── spaces/
│
└── images/                      # Static images (brand assets)
```

---

## API & Helper Functions

The system provides helper functions available globally via `includes/auth.php` and `config/database.php`:

### Authentication & Authorization

```php
getDB()              // Returns PDO instance
getCurrentUser()     // Returns logged-in user array or null
isLoggedIn()         // Returns boolean
isAdmin()            // Returns boolean
requireAuth()        // Redirects to login if not authenticated
requireAdmin()       // Redirects if not admin
loginUser($email, $password)   // Validates credentials, starts session
logoutUser()         // Destroys session
validateEmail($email)          // Validates email format
validatePassword($password)    // Returns array of password requirement errors
```

### Policy & Compliance

```php
getPolicyValue($key, $default)    // Returns active policy value from DB
logAudit($userId, $action, $entityType, $entityId, $oldValues, $newValues, $complianceStatus)
// Records action to immutable audit log
```

### UI Helpers

```php
displayFlashMessages()    // Renders success/error/info flash messages from session
htmlspecialchars($str)    // Standard XSS protection
```

---

## Policy Rules Reference

All policies are managed via **Admin → Policy Engine** (`/admin/policies.php`).

### Validation Rules
| Key | Description | Default | Impact |
|-----|-------------|---------|--------|
| `max_images_per_space` | Max images per space | 10 | Prevents gallery overload |
| `max_pending_visits` | Max concurrent pending visit requests per user | 3 | Prevents spam requests |
| `lease_min_duration_days` | Minimum lease duration | 30 | Ensures viable rental periods |
| `lease_max_duration_months` | Maximum lease duration | 24 | Prevents indefinite lock-in |

### Computation Rules
| Key | Description | Default | Impact |
|-----|-------------|---------|--------|
| `payment_grace_period_days` | Days before marked overdue | 5 | Fair grace period |
| `late_fee_percentage` | Late fee as % of rent | 5% | Deterrent for late payment |
| `deposit_percentage` | Deposit as % of monthly rent | 50% | Standard security deposit |

### Workflow Rules
| Key | Description | Default | Impact |
|-----|-------------|---------|--------|
| `visit_request_expiry_days` | Days before pending visit auto-cancels | 7 | Clears stale requests |
| `require_lease_signing` | Both parties must sign | true | Legal compliance |

### Notification Rules
| Key | Description | Default | Impact |
|-----|-------------|---------|--------|
| `lease_expiry_reminder_days` | Days before expiry to remind | 30 | Prevents unintentional lapse |
| `rent_increase_notice_days` | Notice required for rent increase | 30 | Tenant protection |

---

## Security & Compliance

### Access Control (RBAC)
- Three roles: Admin (level 100), Customer (10), Visitor (0)
- Admin-only pages are protected by `requireAdmin()` which checks session role
- Customer-only pages (profile, testimonials submission) use `requireAuth()`
- All database queries use **prepared statements** to prevent SQL injection

### Input Validation & Output Encoding
- All user inputs validated before processing
- All output uses `htmlspecialchars()` to prevent XSS attacks
- Email format enforced with database CHECK constraint + PHP validation
- Password strength enforced (min 8 chars, upper + lower + number)

### Audit Compliance
- Every state-changing action logged to `audit_log` with:
  - Timestamp, user, action type, entity
  - JSON snapshots of before/after state
  - Compliance status (pass/fail/pending)
  - Policy violation descriptions
- Audit log is append-only (no UPDATE or DELETE operations)

### Database Compliance
- Foreign key constraints enforce referential integrity
- CHECK constraints enforce data validity (email format, ratings 1-5, dates, amounts)
- UNIQUE constraints prevent duplicate records
- Triggers enforce automatic compliance (space status sync, audit logging)
- ENUM types restrict status values to valid state machine transitions

### Session Security (Policy: 5-Minute Inactivity Timeout)

The system enforces strict session lifecycle controls:

| Feature | Implementation |
|---------|---------------|
| **Browser-close logout** | Session cookie is configured with `lifetime = 0`, so the cookie (and thus the session) is automatically destroyed when the browser is closed |
| **Inactivity timeout** | Server-side check runs on **every page load** (`checkSessionTimeout()`). If no activity for **5 minutes (300 seconds)**, the session is destroyed and the user is redirected to login |
| **Client-side detection** | JavaScript tracks user activity (click, keydown, mousemove, scroll, touch). At 4.5 minutes a **30-second warning modal** appears ("Session Expiring Soon"). If the user clicks "Continue", the timer resets. At 5 minutes, the user is auto-redirected to logout |
| **Session fixation protection** | `session_regenerate_id(true)` is called on every login |
| **Cookie hardening** | `HttpOnly` (prevents JS access), `SameSite=Lax` (CSRF protection), custom session name `ZAHARA_SESSION` |
| **Compliance audit** | Both server-side timeout and client-side expiry are logged to the audit trail with action `session.timeout` |
| **Reliable logout** | Uses `navigator.sendBeacon()` for best-effort server-side session destruction when the tab/browser is closed |

**Session Flow:**
```
Login → session_regenerate_id() → last_activity = now()
    │
    ├── User active (click/scroll/key) → last_activity updated on each request
    │
    ├── No activity for 4.5 min → warning modal shown (30s countdown)
    │       │
    │       └── User clicks "Continue" → timer resets
    │
    └── No activity for 5 min → session destroyed → redirected to login
```

**Server-side enforcement** (`includes/auth.php`):
```php
// Session cookie expires on browser close
session_set_cookie_params(['lifetime' => 0, 'httponly' => true, 'samesite' => 'Lax']);

// Check inactivity on every request
define('SESSION_TIMEOUT', 300);  // 5 minutes

function checkSessionTimeout(): void {
    if (isset($_SESSION['last_activity'])) {
        $inactive = time() - $_SESSION['last_activity'];
        if ($inactive > SESSION_TIMEOUT) {
            logAudit($userId, 'session.timeout', ...);
            session_destroy();
            header('Location: /public/login.php');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}
```

**Client-side enforcement** (`assets/js/main.js`):
```js
const SESSION_TIMEOUT_MS = 5 * 60 * 1000;  // 5 minutes
const WARNING_BEFORE_MS = 30 * 1000;       // 30s warning

// Activity listeners: click, keydown, mousemove, scroll, touchstart
// → resets idle timer on any user activity
// → warning modal at 4:30
// → auto-redirect to logout.php?expired=1 at 5:00
```

---

## License

This project is proprietary software. All rights reserved.

---

*Built with PHP 8.0, MariaDB, and Leaflet.js — Policy-as-Code compliance framework enforced throughout.*
