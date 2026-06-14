---
title: "UniMove Res Essentials — User Manual"
subtitle: "Project Deliverable 3"
---

# Project Deliverable 3 — User Manual

| | |
|---|---|
| **Module** | ITECA3-34 — Web Development and e-Commerce |
| **Project** | UniMove Res Essentials — Student-to-Student Campus Marketplace |
| **Student** | Stephan Esterhuizen — EDUV4863668 |
| **Submission Date** | 2026/06/12 |
| **Live URL** | http://unimove-res-essentials.infinityfreeapp.com |
| **Source Code** | https://github.com/StephanEsterhuizen/unimove_iteca |

---

# 1. Introduction

UniMove Res Essentials is a Consumer-to-Consumer (C2C) e-commerce platform designed for South African university students to safely buy and sell residence essentials such as furniture, bedding, appliances, textbooks, clothing, and electronics within a verified, campus-regulated environment.

The platform consists of a **main marketplace** for students and an **admin website** that enforces Role-Based Access Control (RBAC) for moderators and administrators. This manual describes the technical stack, every feature, and how to use them step-by-step.

---

# 2. Technical Stack

| Layer | Technology | Purpose |
|---|---|---|
| Frontend | HTML5, CSS3, Tailwind CSS (CDN), Vanilla JavaScript (ES6+), Lucide icons | Markup, responsive design, live search, OTP input, AJAX |
| Backend | PHP 8.1, PDO + MySQL driver | Server logic, sessions, prepared SQL |
| Database | MySQL 8.0 (InnoDB, utf8mb4) | 10 tables (users, listings, orders, etc.) |
| Email | PHPMailer 6.9.1 + Gmail SMTP | Real OTP email delivery |
| Security | `password_hash` (bcrypt), CSRF tokens, prepared statements, `htmlspecialchars`, MIME-validated uploads, RBAC via `require_role()` | Defense in depth |
| Hosting | InfinityFree (free PHP + MySQL) | Live deployment + free subdomain |
| Dev environment | Docker (PHP-Apache + MySQL + phpMyAdmin), Git + GitHub | Local dev + source control |

---

# 3. System Features

## 3.1 Customer (Student) Features

- **Account** — 3-step registration (university email → OTP → details), login/logout, public profile with ratings and reviews
- **Browsing** — home page with featured listings; browse page with live AJAX search and filters (category, condition, price, pickup zone, bundles)
- **Selling** — create/edit/delete listings (≥ 3 images, ≥ 50-char description required); set price, category, condition, pickup zone
- **Buying** — book a pickup timeslot; order auto-generates a buyer OTP and a seller OTP
- **Handover** — at pickup, each party enters the OTHER party's OTP; order completes when both confirm
- **Messaging** — 2-pane chat with 5-second auto-refresh; unread badge on the navbar
- **Reviews & reports** — 1–5 star review after a completed order; report any listing or user with a reason
- **Dashboard** — three tabs: My Listings, Orders, Messages, with stat cards

## 3.2 Administrator Features

- **Separate admin login** at `/admin/` — refuses any non-admin/moderator account
- **Dashboard** — KPI cards (users, listings, orders, reports), alert pills, recent activity
- **User management (RBAC CRUD)** — verify, suspend, reinstate, change role (admin-only)
- **Listing moderation** — approve, flag, soft-remove, restore, permanently delete (admin-only)
- **Pickup zones + timeslots** (admin-only) — create, edit, delete zones; add/delete timeslots
- **Orders audit log** — full transaction history with status counters and GMV
- **Reports queue** — Open → Reviewed → Resolved workflow with reopen support

**RBAC matrix**

| Action | Admin | Moderator |
|---|:-:|:-:|
| View users, verify, suspend | ✓ | ✓ |
| Change user role | ✓ | ✗ |
| Approve, flag, remove listings | ✓ | ✓ |
| Permanently delete listing | ✓ | ✗ |
| Manage pickup zones + timeslots | ✓ | ✗ |
| View orders + reports | ✓ | ✓ |

---

# 4. Operational Guide

All screenshots are from the live deployed site at `http://unimove-res-essentials.infinityfreeapp.com`.

> **Note on Chrome's "Dangerous site" warning:** Chrome flags every `*.infinityfreeapp.com` subdomain as a false positive — the site itself is safe. Click **"Details" → "visit this unsafe site"** to bypass.

## 4.1 Test login credentials (pre-seeded)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@unimove.ac.za` | `Admin@123` |
| Moderator | `mod@unimove.ac.za` | `Mod@1234` |
| Student | `sarah@eduvos.ac.za` | `Test1234` |

## 4.2 Register a new account

1. Click **Sign Up** in the navbar.
2. **Step 1** — enter a university email (`.ac.za`, `.edu`, `vossie.net`, etc.) → **Send Verification Code**.
3. **Step 2** — check your email for the 6-digit code, enter it, click **Verify Code**.
4. **Step 3** — enter your name, university, password (≥ 8 chars) → **Create Account**.

> **[INSERT SCREENSHOT: `manual-register.png`]**
> *Caption:* Registration flow (any one of the 3 steps).

## 4.3 Log in and browse

1. Click **Login** → enter email + password.
2. Click **Browse** in the navbar.
3. Type in the search bar — results refresh live (no Enter needed).
4. Click **Filters** to narrow by category / condition / price / zone.

> **[INSERT SCREENSHOT: `manual-browse.png`]**
> *Caption:* Browse page with live search and filters active.

## 4.4 Create a new listing

1. Click **Sell** in the navbar.
2. Fill in title, description (≥ 50 chars), price, category, condition, pickup zone.
3. Upload **at least 3 photos** (JPG/PNG/WEBP, max 5).
4. Add available pickup timeslots → **Create Listing**.
5. Listing status is `pending` until an admin approves it.

> **[INSERT SCREENSHOT: `manual-create-listing.png`]**
> *Caption:* Create-listing form with image upload + timeslot fields.

## 4.5 Book a pickup and complete the handover

1. Open a listing → scroll to **Available Pickup Times** → click a timeslot → **Book Pickup**.
2. The platform creates the order and shows the **Order Confirmation** page with **two OTPs**: one for you to share, one for the other party to share.
3. At pickup, exchange OTPs in person. Each party enters the OTHER's code into the verification form.
4. Once both confirm, the order is `completed` and the listing is `sold`.

> **[INSERT SCREENSHOT: `manual-order-handover.png`]**
> *Caption:* Order Confirmation page showing the handover OTP and the verification input.

## 4.6 Submit a review

After an order completes:

1. Click 1–5 stars on the order page.
2. Optionally type a short comment.
3. Click **Post review**. The review appears on the other user's profile.

> **[INSERT SCREENSHOT: `manual-review.png`]**

## 4.7 Send a message

1. From a listing detail page, click **Message** next to the seller.
2. The Messages page opens. Type a message → **Send**.
3. The chat auto-refreshes every 5 seconds.

> **[INSERT SCREENSHOT: `manual-messages.png`]**

## 4.8 Administrator workflows

### 4.8.1 Log in to the admin panel

Go to `http://unimove-res-essentials.infinityfreeapp.com/admin/` and sign in with an admin or moderator account.

> **[INSERT SCREENSHOT: `manual-admin-dashboard.png`]**
> *Caption:* Admin dashboard with KPI cards, alert pills, and recent activity feeds.

### 4.8.2 Approve a pending listing

1. Sidebar → **Listings** → switch to the **Pending** tab.
2. Click **Approve** on the listing's row → status flips to `active` and it becomes visible to students.
3. Alternative actions: **Flag**, **Remove** (soft), or 🗑 **Permanently Delete** (admin-only, refuses if any orders exist).

> **[INSERT SCREENSHOT: `manual-admin-listings.png`]**

### 4.8.3 Manage users (RBAC)

1. Sidebar → **Users**.
2. Per-row actions: **Verify**, **Suspend / Reinstate**, and **Change role** (admin-only — switches between Student / Moderator / Admin).

> **[INSERT SCREENSHOT: `manual-admin-users.png`]**

### 4.8.4 Triage a report

1. Sidebar → **Reports** → **Open** tab.
2. Read the reporter's reason, click the linked listing or user to investigate.
3. Click **Mark Reviewed** → then **Resolve** when the case is closed.

> **[INSERT SCREENSHOT: `manual-admin-reports.png`]**

---

# 5. Troubleshooting

| Problem | Fix |
|---|---|
| Chrome shows "Dangerous site" | False positive on `*.infinityfreeapp.com`. Click *Details → visit this unsafe site*. |
| OTP email did not arrive | Check Spam folder. Click **Resend code** on the OTP step. OTPs also write to a server-side log for admin retrieval. |
| Can't log in — "account isn't verified" | Use **Resend code** at the OTP step, verify, then log in. |
| Upload error on Create Listing | InfinityFree limits POSTs to ~10 MB. Compress images at https://tinypng.com or use phone's medium-quality setting. |
| New listing isn't on the Browse page | It's in `pending` status — an admin must approve it. Check your Dashboard's **My Listings** tab. |

---

# 6. Conclusion

This manual covers every feature implemented in UniMove Res Essentials — from registration through buying, selling, messaging, reviewing, and admin moderation. Each workflow has been verified on the live deployed instance at `http://unimove-res-essentials.infinityfreeapp.com`. The next phase is the live presentation, where I will demonstrate every workflow against the deployed site.

---

# Appendix

| | |
|---|---|
| **Live URL** | http://unimove-res-essentials.infinityfreeapp.com |
| **GitHub repository** | https://github.com/StephanEsterhuizen/unimove_iteca |
| **Branches** | `Unimove_dev_work` (development), `production` (stable release) |
| **Database schema (DDL)** | `unimove/schema.sql` |
| **Local Docker stack** | `docker-compose.yml` — `docker compose up -d` boots PHP + MySQL + phpMyAdmin |
