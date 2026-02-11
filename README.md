# Digital LGA - Parallel Civic Infrastructure Platform

**Open-source WordPress plugin enabling Local Government Areas in Nigeria to create transparent, accountable parallel civic infrastructure by collecting voluntary contributions and managing public projects via competitive tenders.**

## Mission

Route around corrupt government middlemen by:
1. Collecting voluntary payroll contributions from businesses/workers
2. Distributing funds directly to underpaid essential personnel (police, fire, sanitation)
3. Managing infrastructure projects via transparent public tender system
4. Enabling citizen participation and verification

## Features

- **Three-Pool Fund System:** Personnel (30%), Infrastructure (60%), Emergency Reserve (10%) - all configurable
- **Transparent Payroll:** Configurable contribution splits between worker and business
- **Public Tender System:** Competitive bidding with citizen vetting period
- **Milestone Escrow:** Companies paid only upon verified delivery
- **Citizen Participation:** Submit ideas, verify projects, rate companies
- **Company Reputation:** Public profiles with ratings, portfolio, and performance metrics
- **Accountability Team:** Inspections, milestone approvals, enforcement with blacklisting
- **Complete Transparency:** Public dashboard showing all transactions, fund balances, and project status
- **Mobile-First:** Built for Nigerian context (cheap phones, slow internet, high contrast)
- **Decentralized:** Each LGA runs their own independent instance

## Requirements

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+
- WooCommerce (payment processing)
- WP Job Manager + Applications (optional, for extended tender features)
- Advanced Custom Fields Pro (optional, for extended service type management)

## Installation

1. Clone repository:
```bash
git clone https://github.com/ToDigitalTech/Digital-LGA.git
```

2. Upload to WordPress:
```bash
cp -r Digital-LGA/digital-lga /path/to/wordpress/wp-content/plugins/
```

3. Activate plugin in WordPress admin (WooCommerce must be active first)

4. Configure at `/wp-admin/admin.php?page=digital-lga-settings`:
   - LGA name and state
   - Contribution percentages (rate, worker/business split)
   - Pool allocations (personnel/infrastructure/emergency)
   - Payment gateway (Paystack or Flutterwave)

5. Set up service types at `/wp-admin/admin.php?page=dlga-service-types`

6. Create WordPress pages with these shortcodes:
   - `[dlga_transparency]` - Public transparency dashboard
   - `[dlga_login]` - Login form
   - `[dlga_register_business]` - Business registration
   - `[dlga_register_citizen]` - Citizen registration
   - `[dlga_register_civil_servant]` - Civil servant registration
   - `[dlga_business_dashboard]` - Business dashboard
   - `[dlga_citizen_dashboard]` - Citizen dashboard
   - `[dlga_civil_servant_dashboard]` - Civil servant dashboard
   - `[dlga_tenders]` - Tender listings
   - `[dlga_submit_job_idea]` - Citizen idea submission
   - `[dlga_process_payroll]` - Payroll processing
   - `[dlga_company_profile]` - Company profile (use with `?company_id=X`)

## User Types

| Role | Registration | Approval | Capabilities |
|------|-------------|----------|--------------|
| Business | `/dlga/register-business/` | Admin required | Payroll, bid on tenders (if public sector) |
| Citizen | `/dlga/register-citizen/` | Auto-approved | Submit ideas, verify projects, comment on bids |
| Civil Servant | `/dlga/register-civil-servant/` | Admin required | Receive monthly distributions |
| Reviewer | Admin assigns | N/A | Review citizen ideas, create tenders |
| Vetter | Admin assigns | N/A | Score bids, select winners |
| Accountability | Admin assigns | N/A | Inspect projects, approve milestones |

## Fund Distribution Model

```
MONTHLY COLLECTION (configurable %)

POOL 1: ESSENTIAL PERSONNEL (default 30%)
  - Police Officers, Fire Service, Sanitation Workers
  - Equal share per verified person

POOL 2: INFRASTRUCTURE TENDERS (default 60%)
  - Competitive bidding with public vetting
  - Milestone-based escrow payments

POOL 3: EMERGENCY RESERVE (default 10%)
  - Accumulated for crisis management
  - Committee approval required
```

## Tender Workflow

1. Citizen submits infrastructure idea with photos
2. Committee reviews and creates official tender with budget
3. Businesses submit bids (7-day bidding period)
4. All bids made public for citizen vetting (7 days)
5. Committee scores and selects winner
6. Milestone-based escrow payments (30/40/30 default split)
7. Accountability team inspects at each milestone
8. Citizens verify completion with photos and ratings
9. Permanent public archive of all completed projects

## Plugin Structure

```
digital-lga/
├── digital-lga.php          # Main plugin file
├── includes/
│   ├── class-dlga-activator.php      # DB tables, roles, defaults
│   ├── class-dlga-roles.php          # Custom roles & capabilities
│   ├── class-dlga-settings.php       # Admin settings & calculations
│   ├── class-dlga-business.php       # Business registration & management
│   ├── class-dlga-citizen.php        # Citizen registration & ideas
│   ├── class-dlga-civil-servant.php  # Civil servant & service types
│   ├── class-dlga-payroll.php        # Payroll processing
│   ├── class-dlga-distribution.php   # Monthly distributions
│   ├── class-dlga-tender.php         # Tender system & bids
│   ├── class-dlga-committee.php      # Committee actions & scoring
│   ├── class-dlga-woocommerce.php    # WooCommerce integration
│   └── class-dlga-transparency.php   # Public dashboard data
├── admin/
│   ├── class-dlga-admin.php          # Admin menus & pages
│   ├── css/dlga-admin.css
│   └── js/dlga-admin.js
├── public/
│   ├── class-dlga-public.php         # Shortcodes & public pages
│   ├── css/dlga-public.css
│   └── js/dlga-public.js
└── templates/
    ├── registration/                 # Registration forms
    ├── dashboard/                    # User dashboards
    ├── tenders/                      # Tender pages
    ├── citizen/                      # Citizen forms
    ├── transparency.php              # Public dashboard
    └── company-profile.php           # Company profiles
```

## Security

- WordPress nonce verification on all forms
- Role-based capability checks on all actions
- Input sanitization with WordPress sanitize functions
- Output escaping with esc_html, esc_attr, esc_url
- Prepared SQL statements for all database queries
- File upload validation (type and size)

## License

Apache License 2.0 - See [LICENSE](LICENSE)

Completely free. No paid features. No licensing fees. Forever.

## Contributing

Contributions welcome! Please open an issue or pull request.

Built for Nigeria. Built by the people. For the people.
