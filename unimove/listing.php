<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); header('Location: 404.php'); exit; }

// Fetch listing + seller
$stmt = $pdo->prepare(
    "SELECT l.*, c.name AS category, pz.name AS pickup_zone, pz.location_description AS pickup_desc,
            u.user_id AS seller_id, u.full_name AS seller_name, u.is_verified AS seller_verified,
            u.created_at AS seller_since
       FROM listings l
       JOIN categories c    ON c.category_id = l.category_id
       JOIN users u         ON u.user_id = l.seller_id
  LEFT JOIN pickup_zones pz ON pz.zone_id = l.pickup_zone_id
      WHERE l.listing_id = ?"
);
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) { http_response_code(404); header('Location: 404.php'); exit; }

// Images
$stmt = $pdo->prepare('SELECT image_path FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, image_id ASC');
$stmt->execute([$id]);
$images = $stmt->fetchAll();
$image_urls = array_map(fn($r) => listing_image_url($r['image_path']), $images);
if (empty($image_urls)) {
    $image_urls = ['https://placehold.co/800x600?text=No+image'];
}

// Available timeslots for this zone
$timeslots = [];
if (!empty($listing['pickup_zone_id'])) {
    $stmt = $pdo->prepare(
        'SELECT slot_id, slot_date, slot_time, is_booked
           FROM timeslots
          WHERE zone_id = ? AND (slot_date > CURDATE() OR (slot_date = CURDATE() AND slot_time > CURTIME()))
       ORDER BY slot_date ASC, slot_time ASC LIMIT 12'
    );
    $stmt->execute([$listing['pickup_zone_id']]);
    $timeslots = $stmt->fetchAll();
}

// Seller rating
$stmt = $pdo->prepare(
    'SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count
       FROM reviews WHERE reviewee_id = ?'
);
$stmt->execute([$listing['seller_id']]);
$rating_row    = $stmt->fetch();
$avg_rating    = round((float)($rating_row['avg_rating'] ?? 0), 1);
$review_count  = (int)($rating_row['review_count'] ?? 0);

$is_owner      = is_logged_in() && current_user_id() === (int)$listing['seller_id'];

// POST: file a report against this listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'report') {
    require_csrf();
    require_login();

    $reason = trim($_POST['reason'] ?? '');
    if ($is_owner) {
        $_SESSION['flash_error'] = 'You cannot report your own listing.';
    } elseif (mb_strlen($reason) < 10) {
        $_SESSION['flash_error'] = 'Please give a reason of at least 10 characters.';
    } else {
        // Block duplicates: same reporter + same listing while still open
        $stmt = $pdo->prepare(
            "SELECT report_id FROM reports
              WHERE reporter_id = ? AND listing_id = ? AND status IN ('open','reviewed')"
        );
        $stmt->execute([current_user_id(), $id]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'You have already reported this listing — our moderators are looking into it.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO reports (reporter_id, listing_id, reason, status)
                 VALUES (?, ?, ?, "open")'
            );
            $stmt->execute([current_user_id(), $id, $reason]);
            $_SESSION['flash_success'] = 'Report submitted. A moderator will review it soon.';
        }
    }
    header('Location: listing.php?id=' . $id);
    exit;
}

// POST: booking (creates an order)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    require_csrf();
    require_login();

    if ($is_owner) {
        $_SESSION['flash_error'] = 'You cannot book your own listing.';
    } else {
        $slot_id = (int)($_POST['slot_id'] ?? 0);
        if ($slot_id <= 0) {
            $_SESSION['flash_error'] = 'Please select a pickup timeslot.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT slot_id, is_booked FROM timeslots WHERE slot_id = ? AND zone_id = ?'
            );
            $stmt->execute([$slot_id, $listing['pickup_zone_id']]);
            $slot = $stmt->fetch();
            if (!$slot || (int)$slot['is_booked'] === 1) {
                $_SESSION['flash_error'] = 'That timeslot is no longer available.';
            } else {
                $buyer_otp  = generate_otp();
                $seller_otp = generate_otp();
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE timeslots SET is_booked = 1 WHERE slot_id = ?')->execute([$slot_id]);
                $stmt = $pdo->prepare(
                    'INSERT INTO orders
                        (listing_id, buyer_id, seller_id, slot_id, status, buyer_otp, seller_otp, total_price)
                     VALUES (?, ?, ?, ?, "confirmed", ?, ?, ?)'
                );
                $stmt->execute([
                    $id, current_user_id(), $listing['seller_id'], $slot_id,
                    $buyer_otp, $seller_otp, $listing['price']
                ]);
                $order_id = (int)$pdo->lastInsertId();
                $pdo->commit();

                header('Location: order-confirm.php?id=' . $order_id);
                exit;
            }
        }
    }
}

$page_title = $listing['title'];
$active_nav = '';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <a href="browse.php" class="text-pink-600 hover:text-pink-700 mb-6 inline-block">← Back to listings</a>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- =================== Images =================== -->
        <div>
            <div class="relative bg-gray-100 rounded-lg overflow-hidden mb-4">
                <img id="galleryMain" data-gallery-main="g1"
                     src="<?= e($image_urls[0]) ?>"
                     alt="<?= e($listing['title']) ?>"
                     class="w-full h-96 object-cover">
            </div>
            <?php if (count($image_urls) > 1): ?>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($image_urls as $i => $url): ?>
                        <button type="button"
                                data-gallery-thumb="g1"
                                data-src="<?= e($url) ?>"
                                class="rounded-lg overflow-hidden border-2 <?= $i === 0 ? 'border-pink-600' : 'border-transparent' ?>">
                            <img src="<?= e($url) ?>" class="w-full h-24 object-cover" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- =================== Details =================== -->
        <div>
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h1 class="text-3xl font-bold mb-2"><?= e($listing['title']) ?></h1>
                <div class="flex items-center gap-2 text-sm text-gray-600 mb-4 flex-wrap">
                    <span class="bg-gray-100 px-2 py-1 rounded"><?= e($listing['condition_type']) ?></span>
                    <span>•</span>
                    <span><?= e($listing['category']) ?></span>
                    <span>•</span>
                    <span>Posted <?= e(date('M j, Y', strtotime($listing['created_at']))) ?></span>
                    <?php if ((int)$listing['is_bundle'] === 1): ?>
                        <span class="bg-pink-100 text-pink-700 px-2 py-1 rounded">Bundle</span>
                    <?php endif; ?>
                </div>

                <div class="text-4xl font-bold text-pink-600 mb-6">R<?= number_format((float)$listing['price'], 2) ?></div>

                <div class="border-t border-gray-200 pt-6 mb-6">
                    <h3 class="font-semibold mb-3">Description</h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-line"><?= e($listing['description']) ?></p>
                </div>

                <?php if (!empty($listing['pickup_zone'])): ?>
                    <div class="border-t border-gray-200 pt-6 mb-6">
                        <div class="flex items-start gap-2 mb-4">
                            <i data-lucide="map-pin" class="icon-md text-gray-600 mt-0.5"></i>
                            <div>
                                <div class="font-semibold">Pickup Location</div>
                                <div class="text-sm text-gray-600"><?= e($listing['pickup_zone']) ?></div>
                                <?php if (!empty($listing['pickup_desc'])): ?>
                                    <div class="text-xs text-gray-500 mt-1"><?= e($listing['pickup_desc']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$is_owner && $listing['status'] === 'active'): ?>
                    <div class="border-t border-gray-200 pt-6 mb-6">
                        <h3 class="font-semibold mb-3 flex items-center gap-2">
                            <i data-lucide="calendar" class="icon-md"></i>
                            Available Pickup Times
                        </h3>

                        <?php if (empty($timeslots)): ?>
                            <p class="text-sm text-gray-500">No timeslots available right now — message the seller to arrange one.</p>
                        <?php else: ?>
                            <form method="POST" action="listing.php?id=<?= (int)$id ?>" id="bookingForm">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="book">
                                <input type="hidden" name="slot_id" id="selectedSlotId" value="">

                                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                    <?php foreach ($timeslots as $slot): ?>
                                        <button type="button"
                                                data-slot-id="<?= (int)$slot['slot_id'] ?>"
                                                <?= (int)$slot['is_booked'] === 1 ? 'disabled' : '' ?>
                                                class="timeslot w-full p-3 rounded-lg border-2 text-left transition-colors <?=
                                                    (int)$slot['is_booked'] === 1
                                                        ? 'border-gray-100 bg-gray-50 cursor-not-allowed opacity-50'
                                                        : 'border-gray-200 hover:border-pink-300' ?>">
                                            <div class="font-medium"><?= e(date('D, M j, Y', strtotime($slot['slot_date']))) ?></div>
                                            <div class="text-sm text-gray-600"><?= e(date('g:i A', strtotime($slot['slot_time']))) ?></div>
                                            <?php if ((int)$slot['is_booked'] === 1): ?>
                                                <div class="text-xs text-red-600 mt-1">Unavailable</div>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="flex gap-3 mt-6">
                                    <button type="submit" id="bookBtn" disabled
                                            class="flex-1 bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                                        Select a timeslot
                                    </button>
                                    <a href="messages.php?seller=<?= (int)$listing['seller_id'] ?>&listing=<?= (int)$id ?>"
                                       class="px-6 py-3 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                                        <i data-lucide="message-square" class="icon-md"></i>
                                        Message
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif ($is_owner): ?>
                    <div class="border-t border-gray-200 pt-6 flex gap-3">
                        <a href="edit-listing.php?id=<?= (int)$id ?>"
                           class="flex-1 bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 text-center font-medium">
                            Edit Listing
                        </a>
                        <a href="dashboard.php"
                           class="flex-1 border-2 border-gray-300 py-3 rounded-lg hover:bg-gray-50 text-center font-medium">
                            Back to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <div class="border-t border-gray-200 pt-6">
                        <div class="bg-gray-100 px-4 py-3 rounded-lg text-sm text-gray-700">
                            This listing is currently <strong><?= e($listing['status']) ?></strong> and not available for purchase.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Report listing -->
            <?php if (!$is_owner && is_logged_in()): ?>
                <div class="mt-3 text-right">
                    <button type="button" id="openReportModal"
                            class="text-sm text-gray-500 hover:text-red-600 inline-flex items-center gap-1">
                        <i data-lucide="flag" class="icon-sm"></i>
                        Report this listing
                    </button>
                </div>
            <?php elseif (!is_logged_in()): ?>
                <div class="mt-3 text-right">
                    <a href="login.php" class="text-sm text-gray-500 hover:text-red-600 inline-flex items-center gap-1">
                        <i data-lucide="flag" class="icon-sm"></i>
                        Log in to report this listing
                    </a>
                </div>
            <?php endif; ?>

            <!-- Seller info -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 mt-4">
                <h3 class="font-semibold mb-4">Seller Information</h3>
                <a href="profile.php?id=<?= (int)$listing['seller_id'] ?>" class="flex items-center gap-4 hover:bg-gray-50 p-2 rounded-lg transition-colors">
                    <div class="w-12 h-12 bg-pink-600 rounded-full flex items-center justify-center text-white font-semibold">
                        <?= e(mb_substr($listing['seller_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold flex items-center gap-1">
                            <?= e($listing['seller_name']) ?>
                            <?php if ((int)$listing['seller_verified'] === 1): ?>
                                <i data-lucide="badge-check" class="icon-sm text-blue-600" title="Verified"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <?php if ($review_count > 0): ?>
                                <div class="flex items-center gap-1">
                                    <i data-lucide="star" class="icon-sm" style="color:#fbbf24; fill:#fbbf24"></i>
                                    <span><?= e((string)$avg_rating) ?></span>
                                </div>
                                <span>•</span>
                                <span><?= (int)$review_count ?> review<?= $review_count === 1 ? '' : 's' ?></span>
                            <?php else: ?>
                                <span class="text-gray-500">No reviews yet</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            Member since <?= e(date('M Y', strtotime($listing['seller_since']))) ?>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============ Report Listing Modal ============ -->
<?php if (!$is_owner && is_logged_in()): ?>
<div id="reportModal"
     class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="flag" class="icon-md text-red-600"></i>
                Report this listing
            </h3>
            <button type="button" id="closeReportModal" class="text-gray-400 hover:text-gray-700">
                <i data-lucide="x" class="icon-md"></i>
            </button>
        </div>

        <p class="text-sm text-gray-600 mb-4">
            Tell our moderators what's wrong with this listing. Reports are confidential
            — the seller will not see who reported them.
        </p>

        <form method="POST" action="listing.php?id=<?= (int)$id ?>" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="report">

            <div>
                <label class="block text-sm font-medium mb-2">Reason</label>
                <textarea name="reason" required minlength="10" rows="4" maxlength="1000"
                          placeholder="e.g. The photos look like stock images, the price seems too good to be true, the seller is asking to meet off-campus…"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm"></textarea>
                <p class="text-xs text-gray-500 mt-1">Minimum 10 characters.</p>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" id="cancelReport"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                    Submit report
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    // Report modal
    const openBtn  = document.getElementById('openReportModal');
    const closeBtn = document.getElementById('closeReportModal');
    const cancelBtn = document.getElementById('cancelReport');
    const modal    = document.getElementById('reportModal');
    if (openBtn && modal) {
        const open  = () => { modal.classList.remove('hidden'); };
        const close = () => { modal.classList.add('hidden'); };
        openBtn.addEventListener('click', open);
        closeBtn?.addEventListener('click', close);
        cancelBtn?.addEventListener('click', close);
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
        });
    }

    const buttons = document.querySelectorAll('.timeslot');
    const slotInput = document.getElementById('selectedSlotId');
    const bookBtn = document.getElementById('bookBtn');

    buttons.forEach(btn => {
        if (btn.disabled) return;
        btn.addEventListener('click', () => {
            buttons.forEach(b => {
                b.classList.remove('border-pink-600', 'bg-pink-50');
                if (!b.disabled) b.classList.add('border-gray-200');
            });
            btn.classList.remove('border-gray-200');
            btn.classList.add('border-pink-600', 'bg-pink-50');
            slotInput.value = btn.dataset.slotId;
            bookBtn.disabled = false;
            bookBtn.textContent = 'Book Pickup';
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
