<?php
/**
 * UniMove Res Essentials — Test Data Seeder
 *
 *   Run this ONCE after `docker compose up -d` (or after importing schema.sql
 *   on InfinityFree) to populate the database with realistic test data:
 *
 *      - 1 admin, 1 moderator, 6 students (all verified, all logged-in-ready)
 *      - Pickup zones already exist from schema.sql
 *      - 21 timeslots across the next 7 days
 *      - 10 sample listings with placeholder images, statuses, categories
 *      - 2 sample orders (one confirmed, one completed with reviews)
 *      - A handful of messages and reviews
 *
 *   Open http://localhost:8000/seed.php  (Docker)
 *   or   https://your-site/seed.php     (live)
 *
 *   The script REFUSES to re-run if test users already exist. To re-seed:
 *      docker compose down -v && docker compose up -d
 *   then visit this page again.
 *
 *   Delete this file in production.
 */

require_once __DIR__ . '/includes/db.php';

$accounts = [
    ['Admin User',       'admin@unimove.ac.za',  'Admin@123', 'admin',     'UniMove HQ'],
    ['Moderator User',   'mod@unimove.ac.za',    'Mod@1234',  'moderator', 'UniMove HQ'],
    ['Sarah Chen',       'sarah@eduvos.ac.za',   'Test1234',  'student',   'Eduvos'],
    ['Mike Johnson',     'mike@eduvos.ac.za',    'Test1234',  'student',   'Eduvos'],
    ['Emma Davis',       'emma@uct.ac.za',       'Test1234',  'student',   'UCT'],
    ['Alex Kim',         'alex@wits.ac.za',      'Test1234',  'student',   'Wits'],
    ['Jordan Lee',       'jordan@up.ac.za',      'Test1234',  'student',   'University of Pretoria'],
    ['Taylor Smith',     'taylor@sun.ac.za',     'Test1234',  'student',   'Stellenbosch'],
];

/* ---- Guard: refuse if seeded ---- */
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
$stmt->execute(['admin@unimove.ac.za']);
$already = $stmt->fetch();

$reset_mode = isset($_GET['reset']) && $_GET['reset'] === 'yes';
$do_seed    = !$already || $reset_mode;

/* ---- Timeslot top-up (always runs, idempotent via NOT EXISTS) ----
 * Pickup timeslots are scheduled for "next 7 days from today" — which means
 * they expire as time passes. Re-running this every visit keeps the demo
 * data fresh without needing a full DB reset.
 */
$slot_topup_count = 0;
try {
    $stmt = $pdo->prepare(
        'INSERT INTO timeslots (zone_id, slot_date, slot_time, is_booked)
         SELECT z.zone_id, d.dt, t.tm, 0
           FROM pickup_zones z
           CROSS JOIN (
             SELECT DATE_ADD(CURDATE(), INTERVAL 1 DAY) AS dt UNION
             SELECT DATE_ADD(CURDATE(), INTERVAL 2 DAY)       UNION
             SELECT DATE_ADD(CURDATE(), INTERVAL 3 DAY)       UNION
             SELECT DATE_ADD(CURDATE(), INTERVAL 4 DAY)       UNION
             SELECT DATE_ADD(CURDATE(), INTERVAL 5 DAY)       UNION
             SELECT DATE_ADD(CURDATE(), INTERVAL 6 DAY)       UNION
             SELECT DATE_ADD(CURDATE(), INTERVAL 7 DAY)
           ) d
           CROSS JOIN (
             SELECT "10:00:00" AS tm UNION SELECT "13:00:00" UNION SELECT "16:00:00"
           ) t
          WHERE z.is_active = 1
            AND NOT EXISTS (
              SELECT 1 FROM timeslots ts
               WHERE ts.zone_id = z.zone_id AND ts.slot_date = d.dt AND ts.slot_time = t.tm
            )'
    );
    $stmt->execute();
    $slot_topup_count = $stmt->rowCount();
} catch (Throwable $e) { /* swallow — pickup_zones may not exist yet on first run */ }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniMove — Seed Test Data</title>
    <style>
        body { font-family: -apple-system, system-ui, "Segoe UI", Roboto, sans-serif; max-width: 820px; margin: 40px auto; padding: 0 20px; color: #1f2330; }
        h1 { color: #C2185B; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:20px; margin: 14px 0; }
        .ok    { color:#15803d; }
        .warn  { color:#b45309; }
        .err   { color:#b91c1c; }
        table  { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 14px; }
        th, td { padding:6px 10px; text-align:left; border-bottom:1px solid #f1f5f9; }
        code   { background: #fce7f3; color:#831843; padding: 2px 6px; border-radius: 4px; }
        a.btn  { display:inline-block; background:#C2185B; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; margin-right:6px; }
        a.btn.outline { background:#fff; color:#C2185B; border:1px solid #C2185B; }
    </style>
</head>
<body>

<h1>UniMove — Test Data Seeder</h1>

<?php if ($slot_topup_count > 0): ?>
    <div class="card ok">
        <strong>✓ Topped up <?= $slot_topup_count ?> fresh timeslots</strong>
        for the next 7 days across all active pickup zones.
        Booking/handover flow is now ready to test.
    </div>
<?php endif; ?>

<?php if ($already && !$reset_mode): ?>
    <div class="card warn">
        <strong>Already seeded.</strong> Test users exist in this database — the rest of the seed is being skipped.<br>
        <span class="ok">Timeslots have been topped up automatically (see above). The rest of the data is unchanged.</span>
        <br><br>
        To completely wipe and restart, run from your terminal:
        <pre>docker compose down -v
docker compose up -d</pre>
        Then visit this page again.
    </div>
<?php endif; ?>

<?php if ($do_seed):
    $created_users = [];
    $errors        = [];

    try {
        $pdo->beginTransaction();

        /* ---------- USERS ---------- */
        foreach ($accounts as [$name, $email, $pw, $role, $uni]) {
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $existing = $stmt->fetch();
            if ($existing) {
                $created_users[$email] = $existing['user_id'];
                continue;
            }
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, is_verified, university)
                 VALUES (?, ?, ?, ?, 1, ?)'
            );
            $stmt->execute([$name, $email, $hash, $role, $uni]);
            $created_users[$email] = (int)$pdo->lastInsertId();
        }

        /* Timeslots are now handled by the top-of-file top-up block,
           which runs every time seed.php is hit (not just on first seed). */
        $zones   = $pdo->query('SELECT zone_id FROM pickup_zones WHERE is_active = 1')->fetchAll();

        /* ---------- LISTINGS ---------- */
        $catMap = [];
        foreach ($pdo->query('SELECT category_id, name FROM categories')->fetchAll() as $c) {
            $catMap[$c['name']] = (int)$c['category_id'];
        }
        $zoneIds = array_column($zones, 'zone_id');

        $listings = [
            ['Sarah Chen',  'MacBook Pro 2022 (M2)',           12000, 'Electronics', 'Like New', 'Excellent condition MacBook Pro 13-inch from 2022. M2 chip, 16GB RAM, 512GB SSD. Comes with original charger and box. Used for one semester, no scratches or damage. Perfect for students needing a reliable laptop.', 'active'],
            ['Sarah Chen',  'iPhone 13 Pro',                    6500, 'Electronics', 'Good',     'iPhone 13 Pro 128GB Sierra Blue. Battery health 89%. Minor scratch on side. Includes charger and case.', 'active'],
            ['Mike Johnson','Calculus Textbook (12th Ed)',       450, 'Textbooks',   'Good',     'Stewart Calculus 12th edition. Some highlighting but all pages intact. Selling because I passed the module last year.', 'active'],
            ['Mike Johnson','Organic Chemistry Notes (Full)',    200, 'Textbooks',   'Like New', 'Comprehensive hand-written notes covering the entire 2nd year syllabus. Got a distinction using these.', 'active'],
            ['Emma Davis',  'IKEA Desk + Chair Set',             800, 'Furniture',   'Fair',     'IKEA Linnmon desk (white) plus a Markus office chair. Some wear but very functional. Pickup only — too big to ship.', 'pending'],
            ['Emma Davis',  'Mini Fridge (50L)',                 750, 'Appliances',  'Good',     'Great little fridge for residence. Cools well, no rust or smell. Has a small freezer compartment.', 'active'],
            ['Alex Kim',    'North Face Winter Jacket',          600, 'Clothing',    'Like New', 'Mens medium. Worn maybe 5 times. Lost the receipt but bought new for over R3000.', 'active'],
            ['Alex Kim',    'Nike Running Shoes UK 9',           400, 'Clothing',    'Fair',     'Good for casual wear, sole still has tread but visible wear.', 'active'],
            ['Jordan Lee',  'Residence Starter Pack (Bundle)',  2500, 'Bundle / Starter Pack', 'Good', 'Bundle includes: bedding set (single), desk lamp, kettle, two-plate stove, microwave, full crockery set for one. Perfect for first-years moving in.', 'active'],
            ['Taylor Smith','Storage Bins (Set of 4)',           250, 'Storage',     'Like New', 'Stackable plastic storage bins with lids. Great for under-bed storage in res.', 'active'],
        ];

        // Topic-relevant image URLs per listing (hand-picked from Unsplash).
        $image_map = [
            'MacBook Pro 2022 (M2)' => [
                'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&h=400&fit=crop',
            ],
            'iPhone 13 Pro' => [
                'https://images.unsplash.com/photo-1592286927505-ed0213d4f58c?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=600&h=400&fit=crop',
            ],
            'Calculus Textbook (12th Ed)' => [
                'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=600&h=400&fit=crop',
            ],
            'Organic Chemistry Notes (Full)' => [
                'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1517842645767-c639042777db?w=600&h=400&fit=crop',
            ],
            'IKEA Desk + Chair Set' => [
                'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1554629947-334ff61d85dc?w=600&h=400&fit=crop',
            ],
            'Mini Fridge (50L)' => [
                'https://images.unsplash.com/photo-1571175443880-49e1d25b2bc5?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1584568694244-14fbdf83bd02?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1610557892470-55d9e80c0bce?w=600&h=400&fit=crop',
            ],
            'North Face Winter Jacket' => [
                'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1544022613-e87ca75a784a?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1539533113208-f6df8cc8b543?w=600&h=400&fit=crop',
            ],
            'Nike Running Shoes UK 9' => [
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1539185441755-769473a23570?w=600&h=400&fit=crop',
            ],
            'Residence Starter Pack (Bundle)' => [
                'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&h=400&fit=crop',
            ],
            'Storage Bins (Set of 4)' => [
                'https://images.unsplash.com/photo-1581539250439-c96689b516dd?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&h=400&fit=crop',
                'https://images.unsplash.com/photo-1556228852-80b6e5eeff06?w=600&h=400&fit=crop',
            ],
        ];

        $listing_ids = [];
        foreach ($listings as $i => [$seller_email_name, $title, $price, $cat, $cond, $desc, $status]) {
            $seller_id_email = array_search($seller_email_name, array_combine(
                array_keys($created_users),
                array_map(fn($e) => array_values(array_filter($accounts, fn($a) => $a[1] === $e))[0][0] ?? '', array_keys($created_users))
            ));
            // simpler: look up by full_name
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE full_name = ? LIMIT 1');
            $stmt->execute([$seller_email_name]);
            $seller_id = (int)$stmt->fetchColumn();
            if (!$seller_id || !isset($catMap[$cat])) continue;

            $zone_id  = $zoneIds[$i % count($zoneIds)];
            $is_bundle = $cat === 'Bundle / Starter Pack' ? 1 : 0;

            $stmt = $pdo->prepare(
                'INSERT INTO listings (seller_id, title, description, price, category_id,
                                       condition_type, pickup_zone_id, is_bundle, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)'
            );
            $stmt->execute([$seller_id, $title, $desc, $price, $catMap[$cat], $cond, $zone_id, $is_bundle, $status, rand(1, 30)]);
            $lid = (int)$pdo->lastInsertId();
            $listing_ids[$title] = $lid;

            // attach 3 topic-relevant images per listing (Unsplash, hand-picked).
            // listing_image_url() in the app detects http(s) URLs and uses them as-is.
            $urls = $image_map[$title] ?? [
                "https://picsum.photos/seed/unimove{$lid}1/600/400",
                "https://picsum.photos/seed/unimove{$lid}2/600/400",
                "https://picsum.photos/seed/unimove{$lid}3/600/400",
            ];
            $insert_img = $pdo->prepare('INSERT INTO listing_images (listing_id, image_path, is_primary) VALUES (?, ?, ?)');
            foreach ($urls as $im => $url) {
                $insert_img->execute([$lid, $url, $im === 0 ? 1 : 0]);
            }
        }

        /* ---------- ORDERS ---------- */
        $sarah_id  = $created_users['sarah@eduvos.ac.za']  ?? 0;
        $mike_id   = $created_users['mike@eduvos.ac.za']   ?? 0;
        $emma_id   = $created_users['emma@uct.ac.za']      ?? 0;
        $alex_id   = $created_users['alex@wits.ac.za']     ?? 0;

        // 1) Confirmed but not yet handed over — Emma buying Mike's textbook
        if (!empty($listing_ids['Calculus Textbook (12th Ed)']) && $emma_id && $mike_id) {
            $lid = $listing_ids['Calculus Textbook (12th Ed)'];
            $stmt = $pdo->prepare(
                "SELECT slot_id FROM timeslots WHERE is_booked = 0 ORDER BY slot_date, slot_time LIMIT 1"
            );
            $stmt->execute();
            $sid = (int)$stmt->fetchColumn();
            $pdo->prepare('UPDATE timeslots SET is_booked = 1 WHERE slot_id = ?')->execute([$sid]);
            $pdo->prepare(
                'INSERT INTO orders (listing_id, buyer_id, seller_id, slot_id, status, buyer_otp, seller_otp, total_price, created_at)
                 VALUES (?, ?, ?, ?, "confirmed", ?, ?, 450, NOW() - INTERVAL 1 DAY)'
            )->execute([$lid, $emma_id, $mike_id, $sid, '482917', '193746']);
        }

        // 2) Completed — Sarah sold the iPhone to Alex, with reviews already in place
        if (!empty($listing_ids['iPhone 13 Pro']) && $sarah_id && $alex_id) {
            $lid = $listing_ids['iPhone 13 Pro'];
            $stmt = $pdo->prepare(
                "SELECT slot_id FROM timeslots WHERE is_booked = 0 ORDER BY slot_date, slot_time LIMIT 1"
            );
            $stmt->execute();
            $sid = (int)$stmt->fetchColumn();
            $pdo->prepare('UPDATE timeslots SET is_booked = 1 WHERE slot_id = ?')->execute([$sid]);
            $pdo->prepare(
                'INSERT INTO orders (listing_id, buyer_id, seller_id, slot_id, status,
                                     buyer_otp, seller_otp, buyer_confirmed, seller_confirmed,
                                     total_price, created_at, completed_at)
                 VALUES (?, ?, ?, ?, "completed", "111111", "222222", 1, 1, 6500,
                         NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 4 DAY)'
            )->execute([$lid, $alex_id, $sarah_id, $sid]);
            $oid = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE listings SET status = "sold" WHERE listing_id = ?')->execute([$lid]);

            $pdo->prepare(
                'INSERT INTO reviews (order_id, reviewer_id, reviewee_id, rating, comment, created_at)
                 VALUES (?, ?, ?, 5, "Super smooth handover. Sarah was on time and the iPhone was exactly as described. Highly recommend!", NOW() - INTERVAL 4 DAY)'
            )->execute([$oid, $alex_id, $sarah_id]);

            $pdo->prepare(
                'INSERT INTO reviews (order_id, reviewer_id, reviewee_id, rating, comment, created_at)
                 VALUES (?, ?, ?, 5, "Great buyer, paid promptly and was very respectful. Would sell to again.", NOW() - INTERVAL 4 DAY)'
            )->execute([$oid, $sarah_id, $alex_id]);
        }

        /* ---------- MESSAGES ---------- */
        if (!empty($listing_ids['MacBook Pro 2022 (M2)']) && $sarah_id && $alex_id) {
            $lid = $listing_ids['MacBook Pro 2022 (M2)'];
            $msgs = [
                [$alex_id, $sarah_id, 'Hi! Is the MacBook still available?'],
                [$sarah_id, $alex_id, 'Yes it is — would you like to book a pickup?'],
                [$alex_id, $sarah_id, 'Great. How firm is the price?'],
                [$sarah_id, $alex_id, 'It is firm I am afraid — but I will throw in a USB-C hub for you.'],
            ];
            foreach ($msgs as $i => [$from, $to, $text]) {
                $pdo->prepare(
                    'INSERT INTO messages (sender_id, receiver_id, listing_id, message_text, is_read, sent_at)
                     VALUES (?, ?, ?, ?, ?, NOW() - INTERVAL ? HOUR)'
                )->execute([$from, $to, $lid, $text, $i < count($msgs) - 1 ? 1 : 0, count($msgs) - $i]);
            }
        }

        /* ---------- A SAMPLE REPORT ---------- */
        if (!empty($listing_ids['Nike Running Shoes UK 9']) && $mike_id) {
            $pdo->prepare(
                'INSERT INTO reports (reporter_id, listing_id, reason, status, created_at)
                 VALUES (?, ?, "Photos look like a stock image — not sure these are the actual shoes for sale.", "open", NOW() - INTERVAL 6 HOUR)'
            )->execute([$mike_id, $listing_ids['Nike Running Shoes UK 9']]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
?>

    <?php if (empty($errors)): ?>
        <div class="card ok"><strong>✓ Test data seeded successfully.</strong></div>

        <div class="card">
            <h2>Login credentials</h2>
            <table>
                <thead><tr><th>Role</th><th>Email</th><th>Password</th></tr></thead>
                <tbody>
                <?php foreach ($accounts as [$n, $e, $p, $r]): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r) ?></strong> · <?= htmlspecialchars($n) ?></td>
                        <td><code><?= htmlspecialchars($e) ?></code></td>
                        <td><code><?= htmlspecialchars($p) ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>What's been seeded</h2>
            <ul>
                <li>10 listings (9 active, 1 pending approval) with placeholder images from picsum.photos</li>
                <li>21 timeslots across 3 pickup zones × 7 days × 3 times</li>
                <li>1 confirmed order: Emma is buying Mike's Calculus textbook — handover OTPs <code>482917</code> (buyer's) and <code>193746</code> (seller's)</li>
                <li>1 completed order: Alex bought Sarah's iPhone — both reviewed each other 5 stars</li>
                <li>A 4-message chat between Alex and Sarah about the MacBook</li>
                <li>1 open report for the admin/moderator to triage</li>
            </ul>
        </div>

        <div class="card">
            <a class="btn"         href="index.php">Go to homepage</a>
            <a class="btn outline" href="login.php">Student login</a>
            <a class="btn outline" href="admin/index.php">Admin login</a>
        </div>
    <?php else: ?>
        <div class="card err">
            <strong>✗ Seed failed.</strong>
            <ul>
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card" style="background:#fef3c7; border-color:#fbbf24;">
    <strong>Security:</strong> delete <code>seed.php</code> from the server before going live.
</div>

</body>
</html>
