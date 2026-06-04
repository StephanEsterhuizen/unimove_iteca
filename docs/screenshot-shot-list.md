# Screenshot Shot-List for Deliverable 2

The rubric wants every page captured at **three viewport sizes**: smartphone, tablet, desktop. Use Chrome DevTools' device toolbar (`Ctrl+Shift+M`) to switch between them — no real devices needed.

| Viewport | Device preset in Chrome | Suggested dimensions |
|---|---|---|
| **Desktop** | "Responsive" — set manually | 1440 × 900 |
| **Tablet** | "iPad" | 768 × 1024 |
| **Smartphone** | "iPhone 14 Pro" | 393 × 852 |

## How to capture

1. Open the page in Chrome.
2. `Ctrl+Shift+M` to toggle device toolbar.
3. Choose the preset above.
4. `Ctrl+Shift+P` → type "screenshot" → choose **"Capture full size screenshot"**. This saves the entire scroll-length of the page as a single PNG into your Downloads folder.
5. Rename and move to `docs/screenshots/<page>-<viewport>.png`.

Alternatively for a single visible-area shot: **Windows + Shift + S** → drag → save.

---

## Required shots — Main C2C site

Save under `docs/screenshots/main/`:

| Shot | URL (Docker) | Save as |
|---|---|---|
| Home | http://localhost:8001/ | `home-{desktop,tablet,mobile}.png` |
| Browse listings | http://localhost:8001/browse.php | `browse-{desktop,tablet,mobile}.png` |
| Listing detail | http://localhost:8001/listing.php?id=1 | `listing-{desktop,tablet,mobile}.png` |
| Register (step 1) | http://localhost:8001/register.php | `register-step1-{desktop,tablet,mobile}.png` |
| Register (OTP) | (submit step 1 first) | `register-otp-{desktop,tablet,mobile}.png` |
| Login | http://localhost:8001/login.php | `login-{desktop,tablet,mobile}.png` |
| Create listing | http://localhost:8001/create-listing.php *(login first)* | `create-listing-{desktop,tablet,mobile}.png` |
| Dashboard | http://localhost:8001/dashboard.php | `dashboard-{desktop,tablet,mobile}.png` |
| Messages | http://localhost:8001/messages.php | `messages-{desktop,tablet,mobile}.png` |
| Order confirm | http://localhost:8001/order-confirm.php?id=1 *(as Emma)* | `order-confirm-{desktop,tablet,mobile}.png` |
| Profile | http://localhost:8001/profile.php?id=3 | `profile-{desktop,tablet,mobile}.png` |

**Tip:** log in as `sarah@eduvos.ac.za` / `Test1234` to see the dashboard populated. Log in as `emma@uct.ac.za` / `Test1234` to see the order-confirm screen with the confirmed order.

---

## Required shots — Admin site

Save under `docs/screenshots/admin/`:

| Shot | URL | Save as |
|---|---|---|
| Admin login | http://localhost:8001/admin/index.php | `admin-login-{desktop,tablet,mobile}.png` |
| Admin dashboard | http://localhost:8001/admin/dashboard.php | `admin-dashboard-{desktop,tablet,mobile}.png` |
| Users | http://localhost:8001/admin/users.php | `admin-users-{desktop,tablet,mobile}.png` |
| Listings (pending) | http://localhost:8001/admin/listings.php?status=pending | `admin-listings-{desktop,tablet,mobile}.png` |
| Pickup zones | http://localhost:8001/admin/pickup-zones.php?zone=1 | `admin-zones-{desktop,tablet,mobile}.png` |
| Orders | http://localhost:8001/admin/orders.php | `admin-orders-{desktop,tablet,mobile}.png` |
| Reports | http://localhost:8001/admin/reports.php | `admin-reports-{desktop,tablet,mobile}.png` |

Log in as `admin@unimove.ac.za` / `Admin@123` for the admin pages.

---

## Diagrams (single screenshot each)

Open `docs/diagrams.html` in Chrome. Each diagram is in its own framed section. Right-click → *Save image as* on the rendered SVG, or use Snipping Tool.

Save under `docs/screenshots/diagrams/`:

- `eerd.png`
- `context-diagram.png`
- `dfd-level1.png`
- `use-case.png`
- `database-schema.png`

---

## MySQL table screenshots

Open phpMyAdmin at http://localhost:8080 (root / rootpw → select `unimove` database).

For each table — `users`, `listings`, `orders`, `messages`, `reviews`, `reports`, `pickup_zones`, `timeslots`, `categories`, `listing_images` — capture **two** shots:

1. **Structure view** — click the table → *Structure* tab → screenshot the columns list.
2. **Browse view** — click the table → *Browse* tab → screenshot a couple of seeded rows.

Save under `docs/screenshots/mysql/`:

- `users-structure.png`, `users-rows.png`
- `listings-structure.png`, `listings-rows.png`
- … (and so on for each table)
