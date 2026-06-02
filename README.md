# Digital LGA

**A community-run accountability system for Nigerian Local Government Areas — built on code, not trust.**

---

## What is this?

Digital LGA is a free, open-source plugin you install on a WordPress website. Once installed, it turns that website into a full civic management platform for one LGA — handling everything from collecting community funds, to hiring companies for infrastructure projects, to paying civil servants, all in public view.

Think of it as a parallel local government office that runs on software instead of paper — where every naira is tracked, every contract is open to public scrutiny, and no single person can move money without a paper trail.

It is built specifically for Nigeria. It supports Paystack and Flutterwave. It works on cheap phones and slow internet. And it is completely free — no paid tiers, no licensing fees, forever.

---

## The Problem It Solves

Nigerian LGAs collect taxes and receive federal allocations. But residents rarely see that money turn into roads, lights, or functioning drainage. Contracts are awarded in secret. Civil servants go unpaid for months. Nobody knows where the money goes.

Digital LGA routes around that system by letting a community collect and manage its own fund — transparently, with a public dashboard that anyone can open in a browser and see exactly what is happening.

---

## How It Works

### Step 1 — Businesses contribute every month

When a registered business runs payroll for their workers through the platform, a small percentage (set by the LGA — default is 10%) is collected as a community contribution. The worker and business split this cost (configurable, default 50/50).

Payment goes through Paystack or Flutterwave, directly into the LGA fund.

### Step 2 — The money is split into three pools

Every naira collected is automatically divided into three buckets:

| Pool | Default % | What it pays for |
|------|-----------|-----------------|
| Personnel | 30% | Monthly top-ups for civil servants (police, fire, sanitation) |
| Infrastructure | 60% | Roads, drainage, street lights, public buildings — via competitive contracts |
| Emergency Reserve | 10% | Held for crises, requires committee approval to release |

These percentages are configurable by the LGA admin.

### Step 3 — Civil servants get paid automatically every month

Police officers, firefighters, and sanitation workers who are registered and verified on the platform receive an equal share of the personnel pool — automatically, at the start of each month. They get an email notification when their payment is ready. No chasing anyone. No "the chairman travelled."

### Step 4 — Citizens suggest infrastructure projects

Any registered citizen can submit an idea: "The gutter on Adeola Street has been blocked for 3 years," or "We need street lights on the market road." They describe the problem, pick a category, note the location, upload photos, and submit. The committee reviews it within 3 days.

### Step 5 — Projects go to open competitive tender

When the committee approves an idea, they create an official tender with a budget. Registered companies then have 7 days to submit their bids — their proposed cost, timeline, technical approach, team details, and portfolio.

After the bidding period closes, all bids become public for 7 days so citizens can read them and comment. Then the committee scores the bids and selects a winner.

### Step 6 — Companies are paid in milestones, not upfront

The winning company does not receive full payment at the start. Money is released in three tranches:

- **30%** — Project start
- **40%** — 50% completion (verified by the accountability team on-site)
- **30%** — Final completion (verified by the accountability team + citizen ratings)

Each milestone is locked until an accountability team member physically inspects the work and approves it. Citizens can also upload photos and rate the quality of completed work.

### Step 7 — Everything is public, forever

A public transparency dashboard (no login required) shows:
- Live fund pool balances
- Total naira collected this month and all-time
- Number of businesses and workers contributing
- All active and completed projects
- Every company that has ever bid, with their rating and track record
- Monthly collection history going back 12 months
- Civil servant payment summaries by service type

---

## Who Registers on the Platform

| Who | What they do |
|-----|-------------|
| **Business** | Registers their company, adds workers, pays monthly contributions |
| **Citizen** | Registers with a valid ID, submits project ideas, reviews bids, verifies completed work |
| **Civil Servant** | Registers as police, fire service, or sanitation worker — receives monthly distributions |
| **Reviewer** | Committee member who reviews citizen ideas and creates official tenders |
| **Vetter** | Committee member who scores company bids and selects winners |
| **Accountability Officer** | Goes on-site to inspect projects and approve milestone payments |
| **Admin** | Sets up and configures the platform for their LGA |

Businesses and civil servants require admin approval before they are active. Citizens are auto-approved. Committee roles are assigned by the admin.

---

## What Stops People From Cheating

- No single person can release money — milestone payments require accountability team inspection first
- Bids are made public before a winner is selected — citizens can raise red flags
- Companies that fail or abandon projects can be blacklisted and permanently barred from bidding
- Every transaction, payment, and action is logged and visible on the public dashboard
- Payments go through WooCommerce (Paystack/Flutterwave) — not cash, not bank transfers to personal accounts
- The entire platform is open source — anyone can read the code and verify it behaves as described

---

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- MySQL 5.7 or newer
- WooCommerce (required — handles all payments)
- WP Job Manager + Applications (optional)
- Advanced Custom Fields Pro (optional)

---

## Installation

**1. Clone the repository**
```bash
git clone https://github.com/ToDigitalTech/Digital-LGA.git
```

**2. Copy the plugin to your WordPress site**
```bash
cp -r Digital-LGA/digital-lga /path/to/wordpress/wp-content/plugins/
```

**3. Activate in WordPress**

Go to your WordPress admin → Plugins → activate WooCommerce first, then activate Digital LGA.

**4. Configure your LGA**

Go to `WordPress Admin → Digital LGA → Settings` and fill in:
- Your LGA name and state
- Contribution rate (default 10%)
- Worker/business contribution split (default 50/50)
- Pool allocations (personnel / infrastructure / emergency percentages)
- Payment gateway (Paystack or Flutterwave) and API keys

**5. Set up your pages**

Create WordPress pages and add these shortcodes to them:

| Page | Shortcode |
|------|-----------|
| Public transparency dashboard | `[dlga_transparency]` |
| Login | `[dlga_login]` |
| Citizen registration | `[dlga_register_citizen]` |
| Business registration | `[dlga_register_business]` |
| Civil servant registration | `[dlga_register_civil_servant]` |
| Citizen dashboard | `[dlga_citizen_dashboard]` |
| Business dashboard | `[dlga_business_dashboard]` |
| Civil servant dashboard | `[dlga_civil_servant_dashboard]` |
| Tender listings | `[dlga_tenders]` |
| Submit project idea | `[dlga_submit_job_idea]` |
| Process payroll | `[dlga_process_payroll]` |
| Company profile | `[dlga_company_profile]` |

---

## Project Structure

```
digital-lga/
├── digital-lga.php              # Plugin entry point
├── includes/
│   ├── class-dlga-activator.php      # Database setup on install
│   ├── class-dlga-roles.php          # User roles and permissions
│   ├── class-dlga-settings.php       # LGA configuration and payroll math
│   ├── class-dlga-business.php       # Business registration and workers
│   ├── class-dlga-citizen.php        # Citizen registration, ideas, verification
│   ├── class-dlga-civil-servant.php  # Civil servant registration and service types
│   ├── class-dlga-payroll.php        # Payroll processing and fund pool allocation
│   ├── class-dlga-distribution.php   # Automatic monthly civil servant payments
│   ├── class-dlga-tender.php         # Tenders, bids, milestones, company profiles
│   ├── class-dlga-committee.php      # Bid scoring, tender approval, blacklisting
│   ├── class-dlga-woocommerce.php    # Payment gateway integration
│   └── class-dlga-transparency.php   # Public dashboard data
├── admin/                        # WordPress admin interface
├── public/                       # Front-end shortcodes and assets
└── templates/                    # All page templates
    ├── registration/             # Sign-up forms
    ├── dashboard/                # User dashboards
    ├── tenders/                  # Tender listings, single tender, bid forms
    ├── citizen/                  # Idea submission
    ├── transparency.php          # The public dashboard
    └── company-profile.php       # Company public profile
```

---

## Contributing

This project is for Nigeria — and Nigeria has enough developers to make it bulletproof.

If you find a bug, open an issue. If you have an improvement, open a pull request. Whether it's a typo fix, a new feature, or a complete translation to Yoruba, Hausa, or Igbo — contributions are welcome.

---

## License

Apache License 2.0 — see [LICENSE](LICENSE)

Free to use. Free to modify. Free to deploy. No exceptions. No paid version. Forever.

---

*Built for Nigeria. Built by the people. For the people.*
