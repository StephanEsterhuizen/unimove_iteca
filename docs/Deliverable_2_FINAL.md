---
title: "Project Deliverable 2 — Documentation and Coding"
subtitle: "UniMove Res Essentials — A Student-to-Student Campus Marketplace"
author: "[YOUR NAME] — [YOUR STUDENT NUMBER]"
date: "Submission Date: ____________"
---

# Project Deliverable 2 — Documentation and Coding

| | |
|---|---|
| **Faculty** | Information Technology |
| **Module Code** | ITECA3-34 |
| **Module Name** | Web Development and e-Commerce |
| **Project Title** | UniMove Res Essentials — Student-to-Student Campus Marketplace |
| **Student Name** | _________________________________ |
| **Student Number** | _________________________________ |
| **Submission Date** | _________________________________ |

---

# 2.1  Introduction

UniMove Res Essentials is a Consumer-to-Consumer (C2C) e-commerce prototype designed for South African university students to buy and sell residence essentials — furniture, bedding, appliances, textbooks, clothing, and electronics — directly with each other in a verified, campus-regulated environment. The platform addresses four problems identified in Deliverable 1: unsafe meet-ups, scam risk, student affordability, and the fragmented nature of current resale channels (WhatsApp groups, Facebook Marketplace).

The prototype is built strictly on the prescribed stack — **HTML, CSS, JavaScript, PHP, and MySQL** — with Tailwind CSS (a permitted utility framework) supplying the responsive design system. No CMS is used. The platform comprises two coordinated sites: a **main C2C marketplace** (registration with university-email and OTP verification, browsing, listing creation with quality checks, on-platform messaging, time-slotted pickup booking, dual handover OTP, and post-transaction reviews) and an **admin website** that enforces Role-Based Access Control (RBAC) with three role tiers — *Student*, *Moderator*, and *Admin* — and supports full CRUD operations on users, listings, pickup zones, timeslots, orders, and reports.

The application is deployable to any free PHP/MySQL host such as InfinityFree, satisfying the live-hosting requirement.

---

# 2.2  Prototyping

The application is fully responsive thanks to Tailwind CSS's mobile-first utility system. Each page below was tested at three viewport sizes using Chrome DevTools (`Ctrl+Shift+M`):

- **Desktop:** 1440 × 900
- **Tablet:** 768 × 1024 (iPad)
- **Smartphone:** 393 × 852 (iPhone 14 Pro)

## a. Main Website

### Home page

> **[INSERT SCREENSHOT: `home-desktop.png`]**
> *Caption:* Home page — desktop view. Hero, search bar, category quick-links, featured listings, and value-proposition cards.

> **[INSERT SCREENSHOT: `home-tablet.png`]**
> *Caption:* Home page — tablet view. Categories collapse to 2 columns; featured listings collapse to 2 columns.

> **[INSERT SCREENSHOT: `home-mobile.png`]**
> *Caption:* Home page — mobile view. All elements stack to a single column; navigation collapses behind the hamburger.

### Browse listings (with live search + filters)

> **[INSERT SCREENSHOT: `browse-desktop.png`]**
> *Caption:* Browse page with filter drawer open. Search results update live as the user types (no page reload).

> **[INSERT SCREENSHOT: `browse-tablet.png`]**

> **[INSERT SCREENSHOT: `browse-mobile.png`]**

### Listing detail (image gallery + booking)

> **[INSERT SCREENSHOT: `listing-desktop.png`]**
> *Caption:* Listing detail. Image gallery, seller card with rating and verified badge, available pickup timeslots, "Buy Now" and "Message Seller" actions, and a "Report this listing" link.

> **[INSERT SCREENSHOT: `listing-tablet.png`]**

> **[INSERT SCREENSHOT: `listing-mobile.png`]**

### Registration (3-step OTP-gated)

> **[INSERT SCREENSHOT: `register-step1-desktop.png`]**
> *Caption:* Step 1 — accepts only recognised university-email suffixes (`.ac.za`, `.edu`, `vossie.net`, `mygsm.school`, `iielearn.ac.za`).

> **[INSERT SCREENSHOT: `register-otp-desktop.png`]**
> *Caption:* Step 2 — 6-digit OTP entry with auto-advance and paste support.

> **[INSERT SCREENSHOT: `register-details-desktop.png`]**
> *Caption:* Step 3 — full name, university, password (bcrypt-hashed server-side).

> **[INSERT SCREENSHOT: `register-mobile.png`]**

### Login

> **[INSERT SCREENSHOT: `login-desktop.png`]**

> **[INSERT SCREENSHOT: `login-mobile.png`]**

### Create listing

> **[INSERT SCREENSHOT: `create-listing-desktop.png`]**
> *Caption:* Listing-creation form with the quality-check rules from Deliverable 1: minimum 3 images, description ≥50 characters, pickup zone required. Multi-image preview shown.

> **[INSERT SCREENSHOT: `create-listing-mobile.png`]**

### Student dashboard

> **[INSERT SCREENSHOT: `dashboard-desktop.png`]**
> *Caption:* Tabs for My Listings / Orders / Messages, each with real-time counters.

> **[INSERT SCREENSHOT: `dashboard-mobile.png`]**

### Messages (auto-refreshing chat)

> **[INSERT SCREENSHOT: `messages-desktop.png`]**
> *Caption:* Two-pane inbox with a 5-second polling refresh on the active thread.

> **[INSERT SCREENSHOT: `messages-mobile.png`]**

### Order confirmation + handover OTP

> **[INSERT SCREENSHOT: `order-confirm-desktop.png`]**
> *Caption:* Each party reads their own OTP to the other party in person, then enters the OTHER party's code here to confirm the handover. Order completes only when both confirm.

> **[INSERT SCREENSHOT: `order-confirm-mobile.png`]**

### Public profile (with reviews)

> **[INSERT SCREENSHOT: `profile-desktop.png`]**
> *Caption:* Avatar, average rating, member-since, active listings, complete review feed with star-distribution chart.

> **[INSERT SCREENSHOT: `profile-mobile.png`]**

## b. Admin Website

The admin site lives at `/admin/` with a separate login, role-aware sidebar navigation, and server-side RBAC checks on every page (via the `require_role()` helper).

### Admin login (separate from student login)

> **[INSERT SCREENSHOT: `admin-login-desktop.png`]**
> *Caption:* This URL refuses to authenticate any account whose role is not `admin` or `moderator`.

> **[INSERT SCREENSHOT: `admin-login-mobile.png`]**

### Admin dashboard

> **[INSERT SCREENSHOT: `admin-dashboard-desktop.png`]**
> *Caption:* KPI cards (users, active listings, completed orders, open reports), alert pills for pending listings and open reports, and "Recent activity" feeds.

> **[INSERT SCREENSHOT: `admin-dashboard-tablet.png`]**

> **[INSERT SCREENSHOT: `admin-dashboard-mobile.png`]**

### Users (RBAC CRUD)

> **[INSERT SCREENSHOT: `admin-users-desktop.png`]**
> *Caption:* Full CRUD on users. Admins can verify, suspend, reinstate, and change roles between *Student / Moderator / Admin*. Moderators have view + verify + suspend only.

> **[INSERT SCREENSHOT: `admin-users-mobile.png`]**

### Listings moderation

> **[INSERT SCREENSHOT: `admin-listings-desktop.png`]**
> *Caption:* Status tabs (Pending / Active / Flagged / Sold / Removed). Approve, Flag, soft-Remove, Restore. Admins additionally have a **permanent delete** (refuses if any orders reference the listing — preserves transaction history).

### Pickup zones + timeslot management (admin-only)

> **[INSERT SCREENSHOT: `admin-zones-desktop.png`]**
> *Caption:* Left column — list of zones. Right column — edit zone metadata + manage that zone's timeslots (add new ones, delete unbooked ones).

### Orders audit log

> **[INSERT SCREENSHOT: `admin-orders-desktop.png`]**
> *Caption:* Read-only audit log of every transaction with status counters and a Gross Merchandise Value (GMV) stat.

### Reports queue

> **[INSERT SCREENSHOT: `admin-reports-desktop.png`]**
> *Caption:* Open / Reviewed / Resolved tabs with counts. Each report links to the reported listing or user. Workflow buttons: Mark Reviewed → Resolve, or Reopen.

---

# 2.3  Designing

## a. Class Responsibility Collaborator (CRC) Cards

The platform's domain is modelled as 12 conceptual classes. Each card lists the class name, its key responsibilities, and the classes it collaborates with.

---

### Card 1 — `User`
**Responsibilities**
- Hold identity attributes (name, email, university, profile picture).
- Authenticate via password hash and PHP session.
- Track verification state, role (`student / moderator / admin`), and suspension flag.

**Collaborators:** `AuthService`, `Listing`, `Order`, `Message`, `Review`, `Report`

---

### Card 2 — `Listing`
**Responsibilities**
- Represent an item for sale (title, description, price, condition, category).
- Track lifecycle status: `pending → active → sold / flagged / removed`.
- Reference a pickup zone and own one or more `ListingImage`s.
- Enforce quality rules (≥3 photos, description ≥50 chars, pickup zone) before activation.

**Collaborators:** `User` (seller), `Category`, `PickupZone`, `ListingImage`, `Order`, `AdminService`

---

### Card 3 — `Order`
**Responsibilities**
- Represent a confirmed purchase between buyer and seller.
- Generate and store a dual handover OTP pair.
- Transition to `completed` only when both parties confirm.
- Lock a `Timeslot` on creation; release it on cancellation.

**Collaborators:** `Listing`, `User` (buyer + seller), `Timeslot`, `Review`

---

### Card 4 — `Message`
**Responsibilities**
- Persist a chat line between two users (optionally tied to a `Listing` or `Order`).
- Track read/unread state.

**Collaborators:** `User` (sender, receiver), `Listing`, `Order`

---

### Card 5 — `Review`
**Responsibilities**
- Record a 1–5 rating + optional comment posted by one party of a completed order.
- Enforce one review per reviewer per order.
- Aggregate into the `User` profile.

**Collaborators:** `Order`, `User`

---

### Card 6 — `Report`
**Responsibilities**
- Capture a complaint against either a listing or another user.
- Track moderation state: `open → reviewed → resolved` (reopenable).

**Collaborators:** `User`, `Listing`, `AdminService`

---

### Card 7 — `PickupZone`
**Responsibilities**
- Define a named, campus-verified meet location.
- Own a collection of `Timeslot`s.

**Collaborators:** `Timeslot`, `Listing`, `AdminService`

---

### Card 8 — `Timeslot`
**Responsibilities**
- Represent a single bookable date/time window inside a `PickupZone`.
- Track `is_booked` so only one order can lock it.

**Collaborators:** `PickupZone`, `Order`

---

### Card 9 — `Category`
**Responsibilities**
- Provide a fixed taxonomy of item types.
- Carry an icon name used in the UI.

**Collaborators:** `Listing`

---

### Card 10 — `AuthService` *(conceptual)*
**Responsibilities**
- Validate university email domain.
- Generate, store, and verify 6-digit OTP codes with 15-minute expiry.
- `password_hash` / `password_verify` (bcrypt).
- Manage PHP sessions with regeneration on login (anti-fixation).
- Issue and verify CSRF tokens.

**Collaborators:** `User`, `NotificationService`

---

### Card 11 — `AdminService` *(conceptual)*
**Responsibilities**
- Enforce RBAC (`admin` vs `moderator` vs `student`).
- Approve / flag / remove / permanently-delete listings.
- Verify, suspend, reinstate users; change user roles (admin only).
- CRUD on pickup zones and timeslots.
- Triage `Report`s.

**Collaborators:** `Listing`, `User`, `PickupZone`, `Timeslot`, `Report`

---

### Card 12 — `NotificationService` *(conceptual — `mailer.php`)*
**Responsibilities**
- Send transactional email (OTP codes, order receipts).
- Fall back to a development log file when SMTP is unavailable.

**Collaborators:** `AuthService`, `Order`

---

## b. Enhanced Entity Relationship Diagram (EERD)

> **[INSERT SCREENSHOT: `eerd.png`]** *(captured from `docs/diagrams.html` — Diagram #1)*

The diagram captures the 10 database entities, every attribute (with PKs and FKs marked), and the cardinality of every relationship — 1-to-many (`||--o{`), 1-to-1 optional (`||--o|`), and dual self-references (a user can act as both buyer and seller of orders, both reviewer and reviewee of reviews, etc.).

The full DDL for these tables is in `unimove/schema.sql`, and the actual implemented MySQL tables are shown in **§ 2.4(f) Sample MySQL Table Screenshots** below.

**Tables implemented (10 total, InnoDB / utf8mb4):**

| Table | Purpose |
|---|---|
| `users` | Identity, role, verification, suspension |
| `categories` | Listing taxonomy (Electronics, Textbooks, …) |
| `pickup_zones` | Campus-verified meet locations |
| `timeslots` | Bookable date/time slots per zone |
| `listings` | Items for sale + lifecycle status |
| `listing_images` | Multiple images per listing (FK CASCADE) |
| `orders` | Buyer/seller transactions + dual OTPs |
| `messages` | On-platform chat |
| `reviews` | Post-transaction ratings |
| `reports` | Flag-and-resolve workflow |

## c. Context Diagram (Level 0)

> **[INSERT SCREENSHOT: `context-diagram.png`]** *(captured from `docs/diagrams.html` — Diagram #2)*

Four external actors interact with the UniMove platform: **Student/User**, **Admin/Moderator**, an external **Email Service** (for OTPs), and the on-campus **Security** function (which physically operates the pickup zones the system schedules).

## d. Data Flow Diagram (Level 1)

> **[INSERT SCREENSHOT: `dfd-level1.png`]** *(captured from `docs/diagrams.html` — Diagram #3)*

The system decomposes into eight numbered processes — Register, Browse, Create Listing, Place Order, Handover, Messaging, Reviews, Admin Moderation — interacting with eight data stores (D1 – D8). Data flows are labelled with the information being exchanged.

## e. Use Case Diagram

> **[INSERT SCREENSHOT: `use-case.png`]** *(captured from `docs/diagrams.html` — Diagram #4)*

Twenty-six use cases mapped to four actors (Student, Moderator, Admin, Email Service). The OTP verification use case has a stereotyped `<<include>>` relationship from the Register use case, and an outgoing flow to the Email Service.

---

# 2.4  Coding

## a. Screenshots of the working platform

> **[INSERT SCREENSHOT: `platform-home-live.png`]**
> *Caption:* The live, deployed home page on InfinityFree.

> **[INSERT SCREENSHOT: `platform-listing-detail-live.png`]**
> *Caption:* Listing detail page showing the booking flow.

> **[INSERT SCREENSHOT: `platform-admin-dashboard-live.png`]**
> *Caption:* Admin dashboard on the live server.

## b. Sample PHP Code — Secure Login

**File:** `unimove/login.php`

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();                                  // CSRF token check

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw    = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($pw === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(                       // prepared statement → no SQL injection
            'SELECT user_id, full_name, email, password_hash, role, is_verified, is_suspended
               FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($pw, $u['password_hash'])) {   // bcrypt verify
            $errors[] = 'Incorrect email or password.';
        } elseif ((int)$u['is_suspended'] === 1) {
            $errors[] = 'This account has been suspended.';
        } elseif ((int)$u['is_verified'] === 0) {
            header('Location: register.php?step=otp');             // force OTP completion
            exit;
        } else {
            login_user($u);                          // regenerates session ID
            $dest = in_array($u['role'], ['admin', 'moderator'], true)
                  ? 'admin/dashboard.php' : 'dashboard.php';
            header('Location: ' . $dest);
            exit;
        }
    }
}
```

**Explanation:** demonstrates four security layers in the login flow:

1. **CSRF token verification** via `require_csrf()` to prevent cross-site request forgery.
2. **Input sanitisation** with `filter_var(..., FILTER_VALIDATE_EMAIL)`.
3. **Parameterised SQL** (`$stmt = $pdo->prepare(...)` then `$stmt->execute([$email])`) — completely prevents SQL injection.
4. **Bcrypt password verification** via `password_verify()`, which compares the entered password against the stored hash in constant time.

After a successful login, `login_user()` regenerates the PHP session ID (mitigating session-fixation attacks), then routes the user to either the student dashboard or the admin panel based on their role.

## c. Sample HTML Code — Reusable Listing Card

**File:** `unimove/index.php` (used identically on `index.php`, `browse.php`, and `profile.php`)

```html
<a href="listing.php?id=<?= (int)$l['listing_id'] ?>"
   class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-shadow">
    <img src="<?= e(listing_image_url($l['image'])) ?>"
         alt="<?= e($l['title']) ?>"
         class="w-full h-48 object-cover bg-gray-100"
         onerror="this.src='https://placehold.co/400x300?text=No+image'">
    <div class="p-4">
        <h3 class="font-semibold mb-2 truncate"><?= e($l['title']) ?></h3>
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl font-bold text-pink-600">
                R<?= number_format((float)$l['price'], 2) ?>
            </span>
            <span class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                <?= e($l['condition_type']) ?>
            </span>
        </div>
        <div class="text-sm text-gray-600 mb-1"><?= e($l['seller_name']) ?></div>
        <div class="text-xs text-gray-500"><?= e($l['pickup_zone'] ?? '—') ?></div>
    </div>
</a>
```

**Explanation:** a reusable listing-card component. Fully responsive via Tailwind utility classes (`w-full`, `h-48`, `truncate`). Every dynamic value is XSS-escaped via the `e()` helper (a wrapper around `htmlspecialchars()` with `ENT_QUOTES`). Prices are formatted with `number_format` to two decimal places with thousand-separators. The `onerror` attribute supplies a graceful fallback if an image fails to load.

## d. Sample JavaScript Code — Live Search

**File:** `unimove/browse.php`

```javascript
const form    = document.querySelector('form[action="browse.php"]');
const results = document.getElementById('browseResults');
const spinner = document.getElementById('searchSpinner');
let timer = null, lastAbort = null;

function buildQuery() {
    const params = new URLSearchParams();
    new FormData(form).forEach((v, k) => {
        if (v !== '' && v !== 'all') params.append(k, v);
    });
    return params;
}

async function runSearch() {
    const params = buildQuery();
    const ajaxParams = new URLSearchParams(params);
    ajaxParams.set('ajax', 'results');

    history.replaceState(null, '', 'browse.php?' + params);
    if (lastAbort) lastAbort.abort();
    lastAbort = new AbortController();
    spinner?.classList.remove('hidden');

    try {
        const res = await fetch('browse.php?' + ajaxParams, {
            credentials: 'same-origin',
            signal: lastAbort.signal,
        });
        results.innerHTML = await res.text();
    } catch (err) {
        if (err.name !== 'AbortError') {
            results.innerHTML = '<div class="text-red-600">Search failed.</div>';
        }
    } finally {
        spinner?.classList.add('hidden');
    }
}

// Search field — debounce 250ms; filters — fire immediately
form.querySelector('input[name="q"]')?.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
});
form.querySelectorAll('select, input[type="checkbox"]').forEach(el => {
    el.addEventListener('change', () => runSearch());
});
```

**Explanation:** implements instant client-side search on the Browse page using the `fetch` API. Three notable techniques:

1. **Debouncing** — the search text input waits 250 ms after the last keystroke before firing a request, so a typist doesn't trigger 30 requests for "macbook".
2. **`AbortController`** — if the user types faster than the server can respond, the previous in-flight request is cancelled (avoids "racey" outdated results being painted last).
3. **`history.replaceState`** — the URL bar is kept in sync with the filters, so any search URL is shareable / bookmarkable and the browser back/forward buttons work.

The server's `browse.php?ajax=results` endpoint returns just the inner HTML of the results region; the page swaps it in with `innerHTML`.

## e. Sample CSS Code — Brand Tokens

**File:** `unimove/assets/css/style.css`

```css
:root {
    --um-primary:      #C2185B;     /* brand magenta */
    --um-primary-dark: #880E4F;
    --um-primary-soft: #FCE4EC;
}

body {
    font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    color: #111827;
    background: #f9fafb;
    -webkit-font-smoothing: antialiased;
}

/* Lucide icons are <svg>s injected at runtime; class-based sizing
   gives us consistent w-/h-equivalents without Tailwind utility class
   conflicts on the wrapper. */
[data-lucide]            { width: 1.25rem; height: 1.25rem; vertical-align: -0.125em; }
[data-lucide].icon-sm    { width: 1rem;    height: 1rem;    }
[data-lucide].icon-xl    { width: 2rem;    height: 2rem;    }
[data-lucide].icon-3xl   { width: 3rem;    height: 3rem;    }

.um-otp-cell {
    width: 48px; height: 56px;
    text-align: center; font-size: 1.4rem; font-weight: 600;
    border: 1px solid #ececf2; border-radius: 8px; background: #fff;
}
.um-otp-cell:focus {
    outline: none;
    border-color: var(--um-primary);
    box-shadow: 0 0 0 .2rem rgba(194, 24, 91, .15);
}
```

**Explanation:** defines three brand-magenta CSS custom properties used across the platform, sets Inter as the global font, and provides utility selectors for the Lucide icon library plus the custom 6-cell OTP input. Because Lucide replaces `<i data-lucide="…">` placeholders with `<svg>` elements at runtime, attribute-selector sizing ensures icons render at consistent dimensions regardless of the surrounding Tailwind classes.

## f. Sample MySQL Table Screenshots

The full schema lives in `unimove/schema.sql` and was imported into both the local Docker MySQL 8 instance and the live InfinityFree MySQL database. The screenshots below come from phpMyAdmin.

### `users` table

> **[INSERT SCREENSHOT: `mysql-users-structure.png`]**
> *Caption:* `users` table — Structure tab in phpMyAdmin. Shows the columns, types, keys, and indexes.

> **[INSERT SCREENSHOT: `mysql-users-rows.png`]**
> *Caption:* `users` table — Browse tab showing seeded test accounts.

### `listings` table

> **[INSERT SCREENSHOT: `mysql-listings-structure.png`]**

> **[INSERT SCREENSHOT: `mysql-listings-rows.png`]**

### `orders` table

> **[INSERT SCREENSHOT: `mysql-orders-structure.png`]**

> **[INSERT SCREENSHOT: `mysql-orders-rows.png`]**

### `reviews` table

> **[INSERT SCREENSHOT: `mysql-reviews-structure.png`]**

> **[INSERT SCREENSHOT: `mysql-reviews-rows.png`]**

### `messages` table

> **[INSERT SCREENSHOT: `mysql-messages-structure.png`]**

> **[INSERT SCREENSHOT: `mysql-messages-rows.png`]**

### `reports` table

> **[INSERT SCREENSHOT: `mysql-reports-structure.png`]**

> **[INSERT SCREENSHOT: `mysql-reports-rows.png`]**

---

# 2.5  Conclusion

The UniMove Res Essentials prototype satisfies every functional requirement listed in the Deliverable 1 proposal: campus-only registration with university-email enforcement, OTP-based account verification, role-based access control (Student / Moderator / Admin), CRUD listings with quality checks, search and filter, on-platform messaging, time-slotted pickup booking, dual handover OTP verification, post-transaction reviews, and a full reporting workflow. Security is enforced through PDO prepared statements, `password_hash`/`password_verify`, CSRF tokens on every POST, server-side input validation, MIME-validated file uploads, and explicit `require_role()` guards on every admin page.

The design diagrams (CRC cards, EERD, Context Diagram, Data Flow Diagram, Use Case Diagram, and Database Design) demonstrate that the architecture was modelled before implementation — and because every diagram is checked into the repository as Mermaid source rendered through a local HTML viewer, any future schema change can be reflected in the diagrams immediately without re-licensing a paid drawing tool.

For Deliverable 3, the next phase will involve documenting the platform in a comprehensive **User Manual** with step-by-step screenshots, hardening the deployment for the live InfinityFree shared host, and preparing a polished walkthrough of the full transaction flow for the live presentation. The prototype is already deployable and seeded with realistic test data so the lecturer can evaluate every feature end-to-end without setup overhead.

---

# Appendices

| | |
|---|---|
| **Live URL** | https://<your-subdomain>.infinityfreeapp.com/ |
| **GitHub repository** | https://github.com/<your-handle>/unimove-res-essentials |
| **Source-code archive** | `unimove-res-essentials.zip` (attached) |
| **Local Docker stack** | `docker-compose.yml` in the repo root — `docker compose up -d` boots the full PHP + MySQL + phpMyAdmin environment |
| **Seed script** | `unimove/seed.php` — populates 8 test users + 10 listings + 21 timeslots + sample orders, messages, reviews |
| **Diagrams viewer** | `docs/diagrams.html` — open in any browser to render all five Mermaid diagrams |
