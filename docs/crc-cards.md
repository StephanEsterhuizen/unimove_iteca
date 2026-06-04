# Class Responsibility Collaborator (CRC) Cards

The UniMove Res Essentials platform is organised around the following conceptual classes. Each card lists the class name, its key responsibilities, and the other classes it collaborates with.

---

### Card 1 — `User`
**Responsibilities**
- Hold identity attributes (name, email, university, profile picture).
- Authenticate via password hash + session.
- Track verification state, role (student / moderator / admin), and suspension flag.
- Provide read access to its own profile and listings.

**Collaborators**
- `AuthService` (registration, login, OTP)
- `Listing` (a user owns many listings as a seller)
- `Order` (a user appears as buyer and/or seller)
- `Message` (sender / receiver)
- `Review` (reviewer / reviewee)
- `Report` (reporter / reported)

---

### Card 2 — `Listing`
**Responsibilities**
- Represent an item offered for sale (title, description, price, condition, category).
- Track its lifecycle status: `pending → active → sold/flagged/removed`.
- Reference a pickup zone and own one or more `ListingImage`s.
- Enforce quality rules (≥3 photos, description ≥50 chars, pickup zone required) before becoming active.

**Collaborators**
- `User` (seller)
- `Category`
- `PickupZone`
- `ListingImage`
- `Order` (a listing can be ordered)
- `AdminService` (approval, flagging, removal)

---

### Card 3 — `Order`
**Responsibilities**
- Represent a confirmed purchase agreement between a buyer and a seller.
- Generate and store a dual handover OTP pair (`buyer_otp`, `seller_otp`).
- Track confirmation flags from each side and transition to `completed` only when both confirm.
- Lock a `Timeslot` upon creation and release it on cancellation.

**Collaborators**
- `Listing`
- `User` (buyer + seller)
- `Timeslot`
- `Review` (an order can have up to two reviews — one per party)
- `NotificationService` (sends pickup reminders)

---

### Card 4 — `Message`
**Responsibilities**
- Persist a chat line between two users, optionally tied to a `Listing` or `Order`.
- Track read/unread state.
- Expose conversation listings grouped by participant pair + listing.

**Collaborators**
- `User` (sender, receiver)
- `Listing` (context)
- `Order` (context)

---

### Card 5 — `Review`
**Responsibilities**
- Record a rating (1–5) and optional comment posted by one party of a completed order about the other.
- Enforce that each user can review each order only once.
- Provide aggregate (avg rating, count, distribution) to `User` profile.

**Collaborators**
- `Order` (must be `completed`)
- `User` (reviewer + reviewee)

---

### Card 6 — `Report`
**Responsibilities**
- Capture a complaint about either a listing or another user with free-text reason.
- Track moderation state: `open → reviewed → resolved` (and reopen).
- Flag the related listing/user to admins for action.

**Collaborators**
- `User` (reporter + reported)
- `Listing` (reported subject)
- `AdminService` (works the queue)

---

### Card 7 — `PickupZone`
**Responsibilities**
- Define a named, campus-verified meet location with description.
- Own a collection of `Timeslot`s.
- Can be activated/deactivated by admins.

**Collaborators**
- `Timeslot`
- `Listing` (listings reference a default pickup zone)
- `AdminService` (CRUD)

---

### Card 8 — `Timeslot`
**Responsibilities**
- Represent a single bookable date/time window inside a `PickupZone`.
- Track `is_booked` so only one order can lock it.
- Be visible to buyers on the listing page when free.

**Collaborators**
- `PickupZone`
- `Order` (locks/releases)

---

### Card 9 — `Category`
**Responsibilities**
- Provide a fixed taxonomy of item types (Furniture, Electronics, Textbooks, …).
- Carry an icon name used in the UI cards.

**Collaborators**
- `Listing` (every listing belongs to one)

---

### Card 10 — `AuthService` (conceptual)
**Responsibilities**
- Validate a university email (`.ac.za` / `.edu`).
- Generate, store, and verify 6-digit OTP codes with 15-minute expiry.
- Hash + verify passwords using `password_hash` / `password_verify`.
- Manage PHP sessions with regeneration on login (anti-fixation).
- Issue/verify CSRF tokens.

**Collaborators**
- `User`
- `NotificationService` (emails the OTP)

---

### Card 11 — `AdminService` (conceptual)
**Responsibilities**
- Enforce RBAC — `admin` vs `moderator` vs `student`.
- Approve / flag / remove listings.
- Verify, suspend, reinstate users.
- Create + update pickup zones and timeslots.
- Change user roles (admin only).
- Triage `Report`s (mark reviewed / resolved / reopen).

**Collaborators**
- `Listing`, `User`, `PickupZone`, `Timeslot`, `Report`

---

### Card 12 — `NotificationService` (conceptual — `mailer.php`)
**Responsibilities**
- Send transactional email (currently OTP codes; future: order receipts).
- Fall back to a development log file when SMTP is unavailable.

**Collaborators**
- `AuthService`, `Order`
