# Deploying UniMove Res Essentials to InfinityFree

This guide walks you through deploying the platform to InfinityFree free hosting so the live URL can be submitted with Deliverable 2. Total time: **~20 minutes**.

InfinityFree provides free PHP 7.4+/8.x hosting with MySQL databases and cPanel access — perfect for student projects.

---

## Prerequisites

- A free InfinityFree account → https://infinityfree.com (sign up takes 2 minutes).
- The `unimove/` folder from this repo on your machine.
- Any FTP client (FileZilla, WinSCP) **or** use the cPanel file manager (web-based, no install).

---

## Step 1 — Create an InfinityFree account + subdomain

1. Go to https://infinityfree.com and click **Sign Up**.
2. Confirm your email.
3. From the client area, click **Create Account**.
4. Choose a free subdomain — e.g. `unimove-essentials.infinityfreeapp.com` — and set a control panel password.
5. Wait ~2 minutes while the hosting account is provisioned.

---

## Step 2 — Create the MySQL database

1. In the client area, click your hosting account → **Control Panel**.
2. Under **MySQL Databases**, create a new database. Note the **database name** (e.g. `if0_12345678_unimove`), **username**, **password**, and **MySQL hostname** (e.g. `sql205.infinityfree.com`).
3. Open **phpMyAdmin** from the same panel.
4. Select your database on the left, click **Import** at the top.
5. Choose `unimove/schema.sql` from your local machine → **Go**.
   Tables are created and pickup zones + categories are seeded.

---

## Step 3 — Configure the app

Edit `unimove/includes/config.php` and replace the DB fallbacks with the values from Step 2:

```php
define('DB_HOST',    getenv('DB_HOST')    ?: 'sql205.infinityfree.com');
define('DB_NAME',    getenv('DB_NAME')    ?: 'if0_12345678_unimove');
define('DB_USER',    getenv('DB_USER')    ?: 'if0_12345678');
define('DB_PASS',    getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'YOUR_DB_PASSWORD');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
```

Also set the site URL if you want absolute links:

```php
define('SITE_URL', 'https://unimove-essentials.infinityfreeapp.com');
```

**Production:** comment out the two error-display lines at the bottom of the file:

```php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
```

---

## Step 4 — Upload the files

### Option A — cPanel File Manager (easier, browser only)

1. Control Panel → **Online File Manager**.
2. Navigate to `htdocs/`.
3. Delete the default `index2.html` if present.
4. **Upload** the entire contents of `unimove/` (not the `unimove/` folder itself — just what's inside) into `htdocs/`.
5. Make sure `htdocs/uploads/listings/` exists (create it if needed) — this is where uploaded images go.

### Option B — FileZilla (faster for many files)

1. In InfinityFree control panel → **FTP Accounts** → copy host, username, password.
2. Open FileZilla → Host: `ftpupload.net` → enter credentials → Connect.
3. On the right pane navigate to `htdocs/`.
4. Drag the contents of your local `unimove/` folder onto `htdocs/`.

---

## Step 5 — Create the first admin account

Open in your browser:

    https://unimove-essentials.infinityfreeapp.com/install-admin.php

Fill in name + email + password → **Create admin** → **delete `install-admin.php` from the server** (use cPanel File Manager → right-click → Delete).

---

## Step 6 — (Optional) Seed test data for the demo

Open:

    https://unimove-essentials.infinityfreeapp.com/seed.php

This populates the database with 8 test users, 10 listings, 21 timeslots, sample orders, messages, and reviews — useful for the live demo.

**After seeding, delete `seed.php` from the server too.**

---

## Step 7 — Test the live site

Open:

    https://unimove-essentials.infinityfreeapp.com/

Try the full flow:
1. Log in as `sarah@eduvos.ac.za` / `Test1234` — see your listings.
2. Log out → log in as `admin@unimove.ac.za` / `Admin@123` → admin dashboard.

---

## Known InfinityFree caveats

- **No outbound SMTP** by default — `mail()` may not deliver. The OTP fallback writes to `uploads/mail.log` which is still accessible via cPanel File Manager (use that during the demo to copy OTP codes).
- **No `mod_security` bypass** — InfinityFree's "Security Settings" page sometimes blocks form POSTs. If you get 403 errors, open **cPanel → Security → Suspicious URLs** and disable temporarily.
- **5-second `mod_security` cookie** — first request after deploy may show "Just a moment…" — refresh once and you're in.

---

## Final submission checklist

- [ ] Live URL works: `https://<your-subdomain>.infinityfreeapp.com/`
- [ ] Login as admin works
- [ ] Login as test student works
- [ ] At least one listing is visible on `/browse.php`
- [ ] `install-admin.php` and `seed.php` are **deleted from the server**
- [ ] DB credentials in `config.php` are committed only to a *private* repo, **not pushed publicly**
- [ ] Live URL pasted into your Deliverable 2 Word document
