# Deliverable 2 — UniMove Res Essentials
**Student-to-Student Campus Marketplace (C2C E-Commerce Prototype)**

---

## Introduction (≤ 200 words)

UniMove Res Essentials is a Consumer-to-Consumer (C2C) e-commerce prototype designed for South African university students to buy and sell residence essentials — furniture, bedding, appliances, textbooks, clothing, and electronics — directly with each other in a verified, campus-regulated environment. The platform addresses four core problems identified in Deliverable 1: unsafe meetups, scam risk, student affordability, and fragmented resale channels (Facebook groups, WhatsApp).

The prototype is built strictly on the prescribed stack — **HTML, CSS, JavaScript, PHP, and MySQL** — with Bootstrap utilities supplied via Tailwind (a permitted utility framework). No CMS is used. The platform consists of two coordinated sites: a **C2C marketplace** for students (registration with university-email + OTP, browse/search, listing creation with quality checks, on-platform messaging, time-slotted pickup booking, dual handover OTP, and post-transaction reviews) and an **admin website** that enforces Role-Based Access Control (RBAC) with three role tiers — Student, Moderator, and Admin — supporting full CRUD over users, listings, pickup zones, timeslots, orders, and reports.

The application runs locally via Docker (PHP 8.1 + MySQL 8) and is designed for deployment to InfinityFree free hosting, satisfying the live-hosting requirement.

---

## 2.1 Prototyping — Responsive Screenshots

Screenshots demonstrating responsive design at three viewports (desktop, tablet, mobile) for both the main site and the admin website are stored in `docs/screenshots/`. See `docs/screenshot-shot-list.md` for the complete shot list and the device-emulator instructions used.

### (a) Main C2C Website
The student-facing site comprises 12 pages. Each is responsive by virtue of Tailwind's mobile-first utility classes plus explicit `md:` and `lg:` breakpoints.

| Page | File | Purpose |
|---|---|---|
| Home | `unimove/index.php` | Hero, category cards, featured listings, value props |
| Register | `unimove/register.php` | 3-step OTP-gated sign-up |
| Login | `unimove/login.php` | Email + password sign-in |
| Browse | `unimove/browse.php` | Filterable, paginated listings |
| Listing detail | `unimove/listing.php` | Image gallery, seller card, timeslot booking |
| Create listing | `unimove/create-listing.php` | Image upload + quality checks |
| Dashboard | `unimove/dashboard.php` | My listings / orders / messages tabs |
| Messages | `unimove/messages.php` | Live 2-pane chat with 5-second auto-refresh |
| Order confirm | `unimove/order-confirm.php` | Dual-OTP handover + review |
| Profile | `unimove/profile.php` | Avg rating, listings, reviews |
| 404 | `unimove/404.php` | Branded not-found page |

### (b) Admin Website
The admin site lives at `/admin/` with its own login and a role-aware sidebar.

| Page | File | Role |
|---|---|---|
| Admin login | `unimove/admin/index.php` | refuses non-admin/moderator accounts |
| Dashboard | `unimove/admin/dashboard.php` | admin + moderator |
| Users (CRUD) | `unimove/admin/users.php` | admin (CRUD) + moderator (view, verify, suspend) |
| Listings (moderate) | `unimove/admin/listings.php` | admin + moderator |
| Pickup zones | `unimove/admin/pickup-zones.php` | admin only |
| Orders (audit) | `unimove/admin/orders.php` | admin + moderator |
| Reports (triage) | `unimove/admin/reports.php` | admin + moderator |

---

## 2.2 Design Diagrams

All diagrams are produced as Mermaid source and rendered in a single self-contained HTML viewer (`docs/diagrams.html`). Open the viewer in any modern browser to render the diagrams, then capture each one with the Windows Snipping Tool (`Win+Shift+S`) or right-click → *Save image as*.

### (a) Class Responsibility Collaborator (CRC) Cards
12 CRC cards covering every conceptual class in the system. See `docs/crc-cards.md`.

The classes are: **User**, **Listing**, **Order**, **Message**, **Review**, **Report**, **PickupZone**, **Timeslot**, **Category**, **AuthService**, **AdminService**, **NotificationService**.

### (b) Enhanced Entity Relationship Diagram (EERD)
Full EERD showing all 10 database entities, their attributes (PKs/FKs marked), and the cardinality of every relationship. See diagram **#1** in `docs/diagrams.html`.

### (c) Context Diagram
Level-0 system context. Four external actors interact with the UniMove platform: Student/User, Admin/Moderator, Email Service, Campus Security. See diagram **#2** in `docs/diagrams.html`.

### (d) Data Flow Diagram (Level 1)
Decomposes the system into 8 numbered processes (Register, Browse, Create Listing, Place Order, Handover, Messaging, Reviews, Admin) showing data flows between processes, actors, and 8 data stores. See diagram **#3** in `docs/diagrams.html`.

### (e) Use Case Diagram
Identifies 26 use cases mapped to four actor types (Student, Moderator, Admin, Email Service) with `<<include>>` for OTP verification. See diagram **#4** in `docs/diagrams.html`.

### (f) Database Design (Schema)
The full DDL is in `unimove/schema.sql`. Visual class-style schema view is diagram **#5** in `docs/diagrams.html`. The 10 tables are: `users`, `categories`, `pickup_zones`, `timeslots`, `listings`, `listing_images`, `orders`, `messages`, `reviews`, `reports`. All use InnoDB with `utf8mb4`, foreign-key constraints, and dedicated indexes.

---

## 2.3 Coding

### (a) Platform Screenshots
See `docs/screenshots/` (captured per `docs/screenshot-shot-list.md`).

### (b) Sample PHP Code — Secure Login Flow
**File:** `unimove/login.php` (lines 18–46)

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();                                  // CSRF token check

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw    = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($pw === '')                                  $errors[] = 'Password is required.';

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
            $dest = in_array($u['role'], ['admin','moderator'], true)
                  ? 'admin/dashboard.php' : 'dashboard.php';
            header('Location: ' . $dest); exit;
        }
    }
}
```

**What it does:** demonstrates four security layers in 25 lines — (1) CSRF token verification, (2) input sanitisation with `filter_var`, (3) parameterised SQL (prevents injection), (4) bcrypt password verification via `password_verify`. After a successful login, `login_user()` regenerates the PHP session ID to mitigate session-fixation attacks, then routes the user by role.

### (c) Sample HTML Code — Listing Card (Tailwind, semantic, accessible)
**File:** `unimove/index.php` (Featured Listings section)

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
            <span class="text-2xl font-bold text-pink-600">R<?= number_format((float)$l['price'], 2) ?></span>
            <span class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                <?= e($l['condition_type']) ?>
            </span>
        </div>
        <div class="text-sm text-gray-600 mb-1"><?= e($l['seller_name']) ?></div>
        <div class="text-xs text-gray-500"><?= e($l['pickup_zone'] ?? '—') ?></div>
    </div>
</a>
```

**What it does:** a single, reusable listing-card pattern used throughout the site. It is fully responsive via Tailwind utilities (`w-full`, `h-48`), every output is escaped with the `e()` helper (XSS-safe), prices are formatted with `number_format`, and broken images are gracefully replaced via an `onerror` fallback to placehold.co.

### (d) Sample JavaScript Code — OTP Input Auto-Advance
**File:** `unimove/assets/js/main.js` (lines 12–43)

```javascript
document.querySelectorAll('.um-otp-boxes').forEach(function (group) {
    const cells     = Array.from(group.querySelectorAll('.um-otp-cell'));
    const targetSel = group.getAttribute('data-otp-target');
    const hidden    = targetSel ? document.querySelector(targetSel) : null;

    function syncHidden() {
        if (hidden) hidden.value = cells.map(c => c.value).join('');
    }

    cells.forEach(function (cell, idx) {
        cell.addEventListener('input', function () {
            cell.value = cell.value.replace(/\D/g, '').slice(0, 1);
            if (cell.value && idx < cells.length - 1) cells[idx + 1].focus();
            syncHidden();
        });
        cell.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !cell.value && idx > 0) cells[idx - 1].focus();
        });
        cell.addEventListener('paste', function (e) {
            const digits = (e.clipboardData||window.clipboardData).getData('text')
                            .replace(/\D/g, '').slice(0, cells.length);
            if (!digits) return;
            e.preventDefault();
            digits.split('').forEach((d, i) => { if (cells[i]) cells[i].value = d; });
            cells[Math.min(digits.length, cells.length - 1)].focus();
            syncHidden();
        });
    });
});
```

**What it does:** improves the OTP entry UX. Six separate `<input>` cells behave like a single field — typing a digit auto-advances, Backspace jumps back, and pasting the full OTP code splits it across cells. The combined value is mirrored into a hidden field so the server-side validation receives a clean 6-digit string. Used on the registration OTP step and on the order-confirm handover screen.

### (e) Sample CSS Code — Brand Tokens + Lucide Icon Sizing
**File:** `unimove/assets/css/style.css` (selected)

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
   gives us w-/h-equivalents without depending on Tailwind hits. */
[data-lucide]            { width: 1.25rem; height: 1.25rem; vertical-align: -0.125em; }
[data-lucide].icon-sm    { width: 1rem;    height: 1rem;    }
[data-lucide].icon-xl    { width: 2rem;    height: 2rem;    }
[data-lucide].icon-3xl   { width: 3rem;    height: 3rem;    }
```

**What it does:** defines the three brand-magenta CSS custom properties used across the platform, sets Inter as the global font, and provides utility selectors for the Lucide icon library. Because Lucide replaces `<i data-lucide="…">` tags with `<svg>` elements at runtime, these custom selectors ensure consistent sizing regardless of the Tailwind utility classes on the wrapper.

### (f) Sample MySQL Table Screenshots
See `docs/screenshots/mysql/` for phpMyAdmin captures of each table's structure (DDL) plus a sample of seeded rows.

---

## 2.4 Conclusion (≤ 200 words)

The UniMove Res Essentials prototype meets every functional requirement listed in the Deliverable 1 proposal: campus-only registration with university-email enforcement, OTP-based account verification, role-based access control (Student/Moderator/Admin), CRUD listings with quality checks, search/filter, on-platform messaging, time-slotted pickup booking, dual handover OTP verification, post-transaction reviews, and a reporting workflow. Security is enforced through prepared statements (PDO), `password_hash`/`password_verify`, CSRF tokens on every POST, server-side input validation, MIME-validated file uploads, and explicit `require_role()` guards on every admin page.

The design diagrams (EERD, Context, DFD Level-1, Use Case, CRC cards) demonstrate that the architecture was modelled before implementation rather than retrofitted. The Mermaid-based diagram pipeline keeps the documentation in version control alongside the code, so updates to the schema or new use cases remain in sync.

For Deliverable 3, the next phase will involve full functional, integration, and user-acceptance testing in a controlled environment; performance hardening for the InfinityFree shared host; and a polished deployment package complete with a user manual. The prototype is already deployable, dockerised, and seeded with realistic test data for examiners to evaluate end-to-end transaction flow.

---

## Appendices

- **A. Source code:** `unimove/`
- **B. Database schema (DDL):** `unimove/schema.sql`
- **C. Test data seeder:** `unimove/seed.php`
- **D. Docker stack:** `docker-compose.yml`, `docker/php/Dockerfile`
- **E. Hosting guide:** `docs/deployment-infinityfree.md`
- **F. Screenshot shot-list:** `docs/screenshot-shot-list.md`
- **G. CRC cards (full text):** `docs/crc-cards.md`
- **H. Diagrams (browser-viewable):** `docs/diagrams.html`
