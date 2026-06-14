---
title: "UniMove Res Essentials — User Manual"
subtitle: "Project Deliverable 3"
---

# Project Deliverable 3 — User Manual

| | |
|---|---|
| **Faculty** | Information Technology |
| **Module Code** | ITECA3-34 |
| **Module Name** | Web Development and e-Commerce |
| **Project Title** | UniMove Res Essentials — Student-to-Student Campus Marketplace |
| **Student Name** | Stephan Esterhuizen |
| **Student Number** | EDUV4863668 |
| **Submission Date** | 2026/06/12 |
| **Live URL** | http://unimove-res-essentials.infinityfreeapp.com |
| **Source Code** | https://github.com/StephanEsterhuizen/unimove_iteca |

---

# Table of Contents

1. Introduction
2. Technical Stack
3. System Features
   - 3.1 Customer (Student) Features
   - 3.2 Administrator Features
4. Operational Guide — Step by Step
   - 4.1 Getting Started
   - 4.2 Customer Workflows
   - 4.3 Administrator Workflows
5. Troubleshooting and FAQ
6. Conclusion

---

# 1. Introduction

This User Manual is a complete guide to using the **UniMove Res Essentials** platform — a Consumer-to-Consumer (C2C) e-commerce site designed for South African university students to safely buy and sell residence essentials such as furniture, bedding, appliances, textbooks, clothing, and electronics, all within a verified, campus-regulated environment.

The platform consists of two coordinated sites:

- A **main C2C marketplace** for students to register, browse, list, message, and transact
- An **admin website** with Role-Based Access Control (RBAC) for moderators and administrators to manage users, listings, pickup zones, orders, and reports

This manual is written for two audiences:

- **Students** who want to use the platform to buy or sell items
- **Administrators and moderators** who manage and moderate the platform

Each major feature is documented with a step-by-step walkthrough supported by screenshots from the live, deployed platform at `http://unimove-res-essentials.infinityfreeapp.com`.

---

# 2. Technical Stack

The platform is built strictly on the prescribed teaching stack, with a small number of additional open-source tools used for design, security, and deployment.

## Frontend

| Technology | Purpose |
|---|---|
| **HTML5** | Markup for every page |
| **CSS3** | Custom styling for elements not handled by the framework |
| **Tailwind CSS** | Utility-first responsive design system, loaded via CDN |
| **Vanilla JavaScript** (ES6+) | Live search, image previews, OTP input auto-advance, AJAX |
| **Lucide Icons** | SVG icon library, loaded via CDN |
| **Inter font** | Body typeface, loaded from Google Fonts |

## Backend

| Technology | Purpose |
|---|---|
| **PHP 8.1** | All server-side logic, session management, request handling |
| **PDO with MySQL driver** | Database access using prepared statements (SQL-injection safe) |
| **PHPMailer 6.9.1** | SMTP email delivery for OTP verification codes |

## Database

| Technology | Purpose |
|---|---|
| **MySQL 8.0** | Relational database, 10 tables, InnoDB engine, `utf8mb4` charset |

## Security

| Technique | Where used |
|---|---|
| `password_hash()` / `password_verify()` (bcrypt) | All user passwords |
| PHP session ID regeneration on login | Anti-session-fixation |
| CSRF tokens on every POST form | Cross-site-request-forgery protection |
| Server-side input validation with `filter_var()` | Email + URL fields |
| `htmlspecialchars()` via the `e()` helper | XSS-safe output everywhere |
| Prepared SQL statements | SQL-injection-safe queries |
| MIME-validated uploads (jpg/png/webp only) | Image safety |
| RBAC enforced via `require_role()` on every admin page | Authorisation |

## Email

| Technology | Purpose |
|---|---|
| **PHPMailer + Gmail SMTP** | Real OTP email delivery from Gmail's STARTTLS server on port 587 |
| Dev log fallback (`uploads/mail.log`) | Local-dev / SMTP-down fallback for retrieving OTPs |

## Hosting

| Layer | Service |
|---|---|
| **Web + PHP** | InfinityFree (free PHP 8.1 hosting) |
| **MySQL** | InfinityFree (free MySQL 8 database) |
| **Domain** | `unimove-res-essentials.infinityfreeapp.com` (free subdomain) |
| **SSL** | Free auto-issued certificate |

## Development environment

| Tool | Purpose |
|---|---|
| **Docker** + Docker Compose | Local dev stack (PHP 8.1 + MySQL 8 + phpMyAdmin in containers) |
| **Git + GitHub** | Source control, two branches (`Unimove_dev_work`, `production`) |
| **VS Code** | Code editor |
| **Mermaid + draw.io style** | Design diagrams (CRC, EERD, Context, DFD, Use Case) |
| **Pandoc** | Markdown → .docx conversion for the deliverable documents |

---

# 3. System Features

## 3.1 Customer (Student) Features

### 3.1.1 Account Management

- **Registration** with a 3-step OTP-gated flow
  - Step 1: enter university email (must end in `.ac.za`, `.edu`, `vossie.net`, `mygsm.school`, or `iielearn.ac.za`)
  - Step 2: enter the 6-digit OTP sent to that email
  - Step 3: enter full name, university, and password (min. 8 characters)
- **Login** with email + password; blocked if not yet verified or if account is suspended
- **Logout** from the user dropdown menu in the navbar
- **Public profile** showing avatar, rating, reviews, active listings, and member-since date

### 3.1.2 Browsing and Discovery

- **Home page** with hero search, category quick-links, featured listings, and "Why UniMove" trust pillars
- **Browse page** with live AJAX search (debounced 250 ms) and filters: category, condition, price range, pickup zone, bundle-only toggle
- **Listing detail page** with image gallery, seller card with verified badge and rating, full description, pickup zone info, and a list of available pickup timeslots

### 3.1.3 Selling

- **Create listing** form with image upload (≥ 3 photos required), title, description (≥ 50 chars), price, category, condition, pickup zone, and bundle toggle
- **Edit own listing** in place
- **Delete own listing** (soft delete) — admin can hard-delete on request

### 3.1.4 Buying and Transactions

- **Book a pickup timeslot** on any listing — locks the slot and creates an order with two handover OTPs (one for buyer, one for seller)
- **Order confirmation page** showing item, pickup time, location, the two OTPs, and the handover-verification form
- **Handover verification** — buyer enters seller's OTP, seller enters buyer's OTP; when both sides confirm, the order is `completed` and the listing is marked `sold`

### 3.1.5 Communication

- **On-platform messaging** with two-pane chat interface
- **Auto-refresh** every 5 seconds via JavaScript `fetch`
- Messages marked as read on open
- Unread message badge in the navbar

### 3.1.6 Trust and Safety

- **Submit a review** after a completed order (1–5 stars + optional comment)
- **Report a listing** with a textarea reason (≥ 10 chars)
- **Report a user** from their profile page

### 3.1.7 Dashboard

- **Three-tab dashboard**: My Listings / Orders / Messages
- Stat cards: active listings count, active orders count, unread messages count
- Per-listing actions: view, edit, soft-delete

## 3.2 Administrator Features

The admin website lives at `/admin/` and has its own login that refuses any account whose role is not `admin` or `moderator`.

### 3.2.1 Dashboard

- KPI cards: total users, active listings, completed orders, open reports
- Alert pills for pending listings and open reports (clickable shortcuts)
- Recent listings feed
- Recent sign-ups feed

### 3.2.2 User Management (RBAC CRUD)

- Search and filter by name, email, university, role, verification status
- Per-user actions:
  - **Verify** manually (skip OTP)
  - **Suspend** / **Reinstate**
  - **Change role** (between Student / Moderator / Admin — admin-only)
  - View profile

| Action | Admin | Moderator |
|---|:-:|:-:|
| View users | ✓ | ✓ |
| Verify user manually | ✓ | ✓ |
| Suspend / reinstate | ✓ | ✓ |
| Change user role | ✓ | ✗ |

### 3.2.3 Listings Moderation

- Status tabs: Pending / Active / Flagged / Sold / Removed
- Per-listing actions:
  - **Approve** (pending → active)
  - **Flag** (mark as suspicious)
  - **Remove** (soft-delete — row stays in DB for audit)
  - **Restore** (un-delete)
  - **Permanently Delete** (admin-only; refuses if any orders reference the listing)

### 3.2.4 Pickup Zone Management (Admin-only)

- Create new pickup zones with name + location description
- Edit existing zones (rename, change description, toggle active)
- Delete zones (also cascades to their timeslots)
- Add new timeslots to a zone (date + time)
- Delete unbooked timeslots

### 3.2.5 Orders Audit Log

- Full table of every transaction with status counters
- Search by item title, buyer name, seller name
- Status tabs: Pending / Confirmed / Completed / Cancelled / Disputed
- Gross Merchandise Value (GMV) statistic at the top

### 3.2.6 Reports Workflow

- Status tabs: Open / Reviewed / Resolved with live counts
- Each report links to the reported listing or user
- Workflow actions: Mark Reviewed → Resolve, or Reopen

---

# 4. Operational Guide — Step by Step

This section walks through every common task with screenshots. All screenshots are from the live deployed platform at `http://unimove-res-essentials.infinityfreeapp.com`.

## 4.1 Getting Started

### 4.1.1 Visiting the site

1. Open any modern web browser (Chrome, Firefox, Edge, Safari)
2. Navigate to **http://unimove-res-essentials.infinityfreeapp.com**

> **[INSERT SCREENSHOT: `manual-home-loaded.png`]**
> *Caption:* The home page on first load.

**Note on Chrome's "Dangerous site" warning:** Chrome's Safe Browsing applies a blanket warning to every `*.infinityfreeapp.com` subdomain because of the high volume of free sites hosted there. UniMove itself is safe. To bypass: click **"Details"** → **"visit this unsafe site"**.

### 4.1.2 Test login credentials (pre-seeded)

For demonstration purposes the live database is seeded with the following accounts:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@unimove.ac.za` | `Admin@123` |
| Moderator | `mod@unimove.ac.za` | `Mod@1234` |
| Student (Sarah) | `sarah@eduvos.ac.za` | `Test1234` |
| Student (Mike) | `mike@eduvos.ac.za` | `Test1234` |
| Student (Emma) | `emma@uct.ac.za` | `Test1234` |
| Student (Alex) | `alex@wits.ac.za` | `Test1234` |
| Student (Jordan) | `jordan@up.ac.za` | `Test1234` |
| Student (Taylor) | `taylor@sun.ac.za` | `Test1234` |

## 4.2 Customer Workflows

### 4.2.1 Register a new account

1. From any page, click **"Sign Up"** in the top-right of the navbar.
2. **Step 1 — Email:** enter your university email (must end in `.ac.za`, `.edu`, `vossie.net`, `mygsm.school`, or `iielearn.ac.za`). Click **"Send Verification Code"**.

> **[INSERT SCREENSHOT: `manual-register-step1.png`]**
> *Caption:* Step 1 of registration — university email entry.

3. **Step 2 — OTP:** check your email inbox for a 6-digit verification code from `UniMove Res Essentials`. Enter the six digits into the cells (the input auto-advances; you can also paste the full code). Click **"Verify Code"**.

> **[INSERT SCREENSHOT: `manual-register-step2-email.png`]**
> *Caption:* The OTP email as delivered to Gmail.

> **[INSERT SCREENSHOT: `manual-register-step2-otp.png`]**
> *Caption:* Step 2 — OTP entry screen.

4. **Step 3 — Details:** enter your full name, university, and a password (minimum 8 characters). Click **"Create Account"**.

> **[INSERT SCREENSHOT: `manual-register-step3.png`]**
> *Caption:* Step 3 — account details and password.

5. You'll be redirected to your dashboard, already logged in.

### 4.2.2 Log in

1. Click **"Login"** in the navbar.
2. Enter your email and password.
3. Click **"Log In"**.

> **[INSERT SCREENSHOT: `manual-login.png`]**

If your account is unverified you'll be redirected to the OTP step. If it's suspended you'll see a notice asking you to contact support.

### 4.2.3 Browse and search listings

1. From the navbar, click **"Browse"** (or use the hero search bar on the home page).
2. The Browse page shows all active listings as cards (3 columns desktop / 2 tablet / 1 mobile).
3. **Live search:** start typing in the search bar — results refresh after 250 ms, no need to press Enter.
4. **Filter:** click the **"Filters"** button to open the drawer. Pick a category, condition, price range, pickup zone, or "Bundles only".

> **[INSERT SCREENSHOT: `manual-browse-filtered.png`]**
> *Caption:* Browse page with the search term "nike" — instantly filters to one result.

### 4.2.4 View a listing's details

1. On the Browse page (or anywhere a listing card appears), click the card.
2. The listing detail page shows the image gallery (click thumbnails to swap the main image), title, price, condition, full description, pickup zone, and available pickup timeslots.

> **[INSERT SCREENSHOT: `manual-listing-detail.png`]**

### 4.2.5 Create a new listing

1. Click **"Sell"** in the navbar (you must be logged in).
2. Fill in:
   - **Title** (≥ 3 characters)
   - **Description** (≥ 50 characters — this is enforced server-side)
   - **Price** (in Rand, must be > 0)
   - **Category** (drop-down)
   - **Condition** (New / Like New / Good / Fair / Poor)
   - **Pickup Zone** (drop-down)
   - **Bundle toggle** (if it's a starter pack of multiple items)
3. **Upload at least 3 images** (max 5). JPG, PNG, or WEBP. The first image becomes the cover.

> **[INSERT SCREENSHOT: `manual-create-listing.png`]**

4. Add available pickup timeslots (date + time) — click "Add another timeslot" to add more.
5. Click **"Create Listing"**.
6. The listing is submitted with status `pending`. An admin must approve it before it appears on the Browse page.

### 4.2.6 Book a pickup and place an order

1. On a listing detail page (one you do not own), scroll to **"Available Pickup Times"**.
2. Click any green timeslot — it highlights pink and the "Select a timeslot" button changes to **"Book Pickup"**.

> **[INSERT SCREENSHOT: `manual-book-timeslot.png`]**

3. Click **"Book Pickup"** to confirm.
4. The platform creates an order, generates a pair of dual OTPs, locks the timeslot, and redirects you to the order confirmation page.

### 4.2.7 Confirm the handover with dual OTP

When you arrive at the agreed pickup location on the agreed date/time:

1. Each party logs into their own account and opens the order confirmation page (`order-confirm.php?id=<order_id>`).
2. The page displays:
   - **Your handover OTP** (which you read aloud to the other party)
   - **A 6-cell input** where you enter the **other party's** OTP

> **[INSERT SCREENSHOT: `manual-order-handover.png`]**

3. When you've verified the goods, type the other person's 6-digit OTP into the input and click **"Verify & Confirm Handover"**.
4. Once **both** parties confirm, the order status flips to `completed` and the listing is marked `sold`. You'll then see a green **"Transaction Complete!"** screen.

### 4.2.8 Submit a review

After an order completes, the order confirmation page shows a **"Rate your experience"** form.

1. Click 1–5 stars to rate the other party.
2. Optionally type a comment (≤ 1000 characters).
3. Click **"Post review"**.

> **[INSERT SCREENSHOT: `manual-review-submit.png`]**

Your review appears immediately on the other user's public profile.

### 4.2.9 Send a message

1. From a listing detail page click **"Message"** next to the seller card.
2. The Messages page opens with a new thread pre-loaded.
3. Type a message and press **Send**.

> **[INSERT SCREENSHOT: `manual-messages.png`]**

Threads on the left, the active conversation on the right. The page polls for new messages every 5 seconds.

### 4.2.10 Report a listing or user

**To report a listing:** open the listing detail page → scroll to the seller card → click the small **"Report this listing"** link → enter a reason (≥ 10 characters) → submit.

**To report a user:** open the user's profile → in the sidebar click **"Report this user"** → enter a reason → submit.

> **[INSERT SCREENSHOT: `manual-report.png`]**

Reports go straight to the admin queue. The reported party is **not** notified of who filed it.

### 4.2.11 Use the dashboard

Click **"Dashboard"** (the grid icon in the navbar). Three tabs:

| Tab | Shows |
|---|---|
| My Listings | Your listings with status badge and view/edit/delete actions |
| Orders | Both buying and selling orders with status, pickup info, and price |
| Messages | Recent conversations grouped by partner |

> **[INSERT SCREENSHOT: `manual-dashboard.png`]**

## 4.3 Administrator Workflows

### 4.3.1 Log in to the admin panel

1. Navigate to **http://unimove-res-essentials.infinityfreeapp.com/admin/index.php**
2. Enter an admin or moderator email/password.

> **[INSERT SCREENSHOT: `manual-admin-login.png`]**

Student accounts are **rejected** here — they must use the regular `/login.php`.

### 4.3.2 View KPIs on the dashboard

The admin dashboard shows total users, active listings, completed orders, and open reports. Yellow and red alert pills at the top link directly to pending listings and open reports.

> **[INSERT SCREENSHOT: `manual-admin-dashboard.png`]**

### 4.3.3 Manage users (verify, suspend, change role)

1. From the sidebar click **"Users"**.
2. Search by name/email/university or filter by role/verification status.
3. Per-row actions appear on the right:
   - **Verify** — manually mark the user as verified
   - **Suspend** / **Reinstate** — toggle suspension
   - **Change role…** dropdown — admins only; switches between Student / Moderator / Admin

> **[INSERT SCREENSHOT: `manual-admin-users.png`]**

### 4.3.4 Approve, flag, or remove listings

1. Click **"Listings"** in the sidebar.
2. Switch to the **"Pending"** tab to see listings awaiting approval.
3. Per-row actions:
   - **Approve** (green) — status → `active`, becomes visible to all students
   - **Flag** (yellow) — status → `flagged`, hidden, marked as suspicious
   - **Remove** (orange) — status → `removed`, soft delete; row preserved for audit
   - **Trash icon** (red) — admin-only **permanent delete**, refuses if any orders reference the listing

> **[INSERT SCREENSHOT: `manual-admin-listings-pending.png`]**

### 4.3.5 Manage pickup zones and timeslots (Admin-only)

1. Sidebar → **"Pickup Zones"** (this menu item is hidden from moderators).
2. **Add a new zone:** fill the form on the left (name + description) → **Create zone**.
3. **Manage timeslots:** click a zone on the left list. The right panel shows that zone's existing slots and a form to add new ones.
4. **Delete:** unbooked slots show a small × icon — click to delete. Booked slots cannot be deleted (this protects existing orders).

> **[INSERT SCREENSHOT: `manual-admin-zones.png`]**

### 4.3.6 View the orders audit log

Click **"Orders"** in the sidebar. The page lists every order ever placed, with:

- Status counters (Pending, Confirmed, Completed, Disputed) and GMV at the top
- A search/filter form
- A full table with order ID, item, buyer, seller, pickup, price, status, and timestamps

> **[INSERT SCREENSHOT: `manual-admin-orders.png`]**

### 4.3.7 Triage reports

1. Sidebar → **"Reports"**.
2. Tabs: **Open** / **Reviewed** / **Resolved** with counts.
3. Each report card shows:
   - Type (Listing or User) and current status
   - The reporter's name
   - A link to the reported listing or user
   - The full reason
4. Workflow buttons:
   - **Mark Reviewed** — moves from Open → Reviewed
   - **Resolve** — closes the report
   - **Reopen** — brings a resolved report back

> **[INSERT SCREENSHOT: `manual-admin-reports.png`]**

---

# 5. Troubleshooting and FAQ

## "Chrome says the site is dangerous when I open the URL"

This is a known false positive on Google Safe Browsing for the entire `*.infinityfreeapp.com` parent domain. UniMove itself is safe. Click **"Details"** → **"visit this unsafe site"** to bypass. Chrome remembers your choice for that domain.

## "I didn't receive the OTP email"

1. Check your **Spam / Junk** folder.
2. Make sure you entered the right email address.
3. Click **"Resend code"** to issue a new OTP (the old one is invalidated).
4. The platform also writes every issued OTP to a server-side log for development purposes; admins can retrieve a code on request.

## "I can't log in — it says my account isn't verified"

Click the **"Resend code"** option at the OTP step, then enter the new code. Once verified you can log in normally.

## "I can't log in — it says my account is suspended"

Contact a UniMove administrator. They can reinstate the account from the admin Users page.

## "I tried to upload images and got an error"

InfinityFree limits uploads to roughly 10 MB total per POST. If you're uploading high-resolution phone photos:

- Use your phone's "medium" or "small" camera quality setting, OR
- Compress the images with a free tool such as https://tinypng.com before uploading

## "The listing I created doesn't show on the Browse page"

New listings start as `pending` and must be approved by an admin or moderator. Once approved they become `active` and visible. Check your dashboard's "My Listings" tab — the status badge will tell you where it is in the workflow.

## "The pickup timeslot I want isn't available"

Timeslots can only be booked once. If yours is booked, either:

- Message the seller to ask them to add a new timeslot via their listing-edit page, or
- Wait for the admin to schedule additional slots on that pickup zone

## "I forgot my password"

Password reset is planned for a future release. For now, contact an administrator to issue a manual password reset.

---

# 6. Conclusion

This manual covers every feature implemented in UniMove Res Essentials — from a brand-new student creating their first account, through buying and selling, to admins moderating reports and managing the platform's pickup infrastructure. Each step has been verified on the live deployed instance hosted at `http://unimove-res-essentials.infinityfreeapp.com`.

The next phase of the project is the **live presentation**, where I will demonstrate each of the workflows in this manual end-to-end against the deployed site. The full source code, this user manual, and the Deliverable 2 design documentation are all available on the GitHub repository at https://github.com/StephanEsterhuizen/unimove_iteca.

---

# Appendices

| | |
|---|---|
| **Live URL** | http://unimove-res-essentials.infinityfreeapp.com |
| **GitHub repository** | https://github.com/StephanEsterhuizen/unimove_iteca |
| **Branches** | `Unimove_dev_work` (development) · `production` (stable release) |
| **Local Docker stack** | `docker-compose.yml` in the repo root — `docker compose up -d` boots PHP + MySQL + phpMyAdmin |
| **Database schema (DDL)** | `unimove/schema.sql` |
| **Test data seeder** | `unimove/seed.php` *(remove from production after demo)* |
| **Deliverable 2 (Design + Coding)** | `docs/Deliverable_2_FIXED.docx` |
