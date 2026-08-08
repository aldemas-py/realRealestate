# 🏢 Zahara Co-Working Space - Office Space Rental System (Westlands, Nairobi)
## Policy-as-Code with Compliance Enforcement

## Phase 1: Foundation ✅
- [x] TODO.md created
- [x] SQL Database Schema (schema.sql) with Westlands/Kenya data
- [x] Database Config (config/database.php)
- [x] Core Includes (header.php, footer.php, auth.php)
- [x] Custom CSS (assets/css/style.css) - Responsive with #1565C0 branding
- [x] Core JS (assets/js/main.js, assets/js/map.js)

## Phase 2: Public Pages ✅
- [x] index.php - Landing page with hero, featured spaces, map, virtual packages
- [x] public/spaces.php - Browse all spaces with type filters & search
- [x] public/space-detail.php - Single space + gallery + map + visit request form
- [x] public/testimonials.php - Reviews with submission form & approval flow
- [x] public/about.php - About page with stats & story
- [x] public/contact.php - Contact form & Krishna Centre location map
- [x] public/login.php - Login page
- [x] public/register.php - Registration with pending admin approval
- [x] public/logout.php - Logout handler
- [x] public/profile.php - User profile with leases, payments, visits, settings tabs

## Phase 3: Admin Panel ✅
- [x] admin/login.php - Admin login (info@zaharacowork.com)
- [x] admin/index.php - Dashboard with all key metrics
- [x] admin/sidebar.php - Navigation sidebar
- [x] admin/spaces/index.php - List all spaces with CRUD actions
- [x] admin/spaces/create.php - Create new space form
- [x] admin/spaces/edit.php - Edit space form
- [x] admin/visit-requests/index.php - Manage visit requests (approve/reject/complete)
- [x] admin/customers/index.php - Customer management (approve/suspend)
- [x] admin/leases/index.php - Lease creation & lifecycle management
- [x] admin/payments/index.php - Rent management, payment recording, overdue tracking
- [x] admin/testimonials/index.php - Testimonial moderation (approve/reject/feature)
- [x] admin/users/index.php - Full user management
- [x] admin/policies.php - **Policy Engine** - Manage compliance rules (Policy-as-Code)
- [x] admin/audit.php - **Audit Log** - Full compliance trail with pagination

## Phase 4: Setup & Database ✅
- [x] setup.php - One-click database installer (seed data, admin user, test data)
- [x] sql/schema.sql - Full schema with triggers & constraints
- [x] sql/migration.sql - Migration support

## Phase 5: Session Security & Timeout ✅
- [x] Session cookie expires on browser close (cookie_lifetime=0)
- [x] 5-minute inactivity timeout (server-side enforcement on every request)
- [x] Client-side proactive inactivity detection with 30-second warning modal
- [x] Auto-redirect to login on session expiry
- [x] Session ID regeneration on login (prevents session fixation)
- [x] HttpOnly + SameSite cookie hardening
- [x] sendBeacon for reliable background logout on browser/tab close
- [x] Session timeout logged to compliance audit trail

## Phase 6: Virtual Office Promotional Flyer ✅
- [x] public/virtual-office.php - Promotional page replicating the official flyer
- [x] Flyer hero with brand image + headline + feature checklist
- [x] Three pricing tiers (Business Presence 3K, Standard 5K, Plus 10K)
- [x] Contact/location CTA section (Krishna Centre, ph/email)
- [x] Added "Virtual Office" navigation tab in site header
- [x] Added Virtual Office promotional space card to spaces listing
- [x] Flyer-specific responsive CSS in assets/css/style.css

## Key Features Implemented
| Feature | Status |
|---------|--------|
| Company: **Zahara Co-Working Space** | ✅ |
| Tagline: **A Replacement of Traditional Workplace** | ✅ |
| Location: **Krishna Centre, 2nd Floor, Westlands, Nairobi** | ✅ |
| Phone: **0724 161 342** / Email: **info@zaharacowork.com** | ✅ |
| Color Scheme: **#1565C0** (blue) + white/grey/black corporate | ✅ |
| Currency: **KES** throughout | ✅ |
| Virtual Packages: Business Presence (3K), Standard (5K), Plus (10K) | ✅ |
| RBAC (Admin/Customer/Visitor) | ✅ |
| Office space CRUD + images | ✅ |
| Map integration (Leaflet/OpenStreetMap - Krishna Centre) | ✅ |
| Visit request state machine | ✅ |
| Lease management with deposit flow | ✅ |
| Payment tracking with overdue detection & late fees | ✅ |
| Automatic space status updates (via triggers) | ✅ |
| Testimonial moderation | ✅ |
| **Policy-as-Code engine** (10 configurable policies) | ✅ |
| **Compliance audit trail** (all actions logged) | ✅ |
| Responsive design | ✅ |
| Account approval workflow | ✅ |
| Profile management | ✅ |
