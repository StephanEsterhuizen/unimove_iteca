<?php
// Order confirmation + dual-OTP handover. Both parties confirm to complete.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();
$uid = current_user_id();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); header('Location: 404.php'); exit; }

$stmt = $pdo->prepare(
    "SELECT o.*, l.title, l.price AS list_price,
            (SELECT image_path FROM listing_images
              WHERE listing_id = l.listing_id
           ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image,
            ub.full_name AS buyer_name,  ub.user_id AS bid,
            us.full_name AS seller_name, us.user_id AS sid,
            t.slot_date, t.slot_time,
            pz.name AS pickup_zone, pz.location_description AS pickup_desc
       FROM orders o
       JOIN listings l        ON l.listing_id = o.listing_id
       JOIN users ub          ON ub.user_id = o.buyer_id
       JOIN users us          ON us.user_id = o.seller_id
  LEFT JOIN timeslots t       ON t.slot_id = o.slot_id
  LEFT JOIN pickup_zones pz   ON pz.zone_id = t.zone_id
      WHERE o.order_id = ?"
);
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) { http_response_code(404); header('Location: 404.php'); exit; }
if ((int)$order['bid'] !== $uid && (int)$order['sid'] !== $uid) {
    http_response_code(403); die('You are not part of this order.');
}

$is_seller = (int)$order['sid'] === $uid;
$is_buyer  = (int)$order['bid'] === $uid;

// POST: verify OTP from the other party
$otp_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_otp') {
    require_csrf();
    $entered = preg_replace('/\D/', '', $_POST['otp'] ?? '');
    if (strlen($entered) !== 6) {
        $otp_error = 'Please enter the 6-digit code.';
    } else {
        // Buyer enters seller_otp; Seller enters buyer_otp
        $expected = $is_buyer ? $order['seller_otp'] : $order['buyer_otp'];
        if (!hash_equals((string)$expected, $entered)) {
            $otp_error = 'Incorrect code. Ask the other party to double-check.';
        } else {
            $field = $is_buyer ? 'buyer_confirmed' : 'seller_confirmed';
            $pdo->prepare("UPDATE orders SET $field = 1 WHERE order_id = ?")->execute([$id]);

            // Refresh order to check both sides
            $stmt = $pdo->prepare('SELECT buyer_confirmed, seller_confirmed FROM orders WHERE order_id = ?');
            $stmt->execute([$id]);
            $confirms = $stmt->fetch();

            if ((int)$confirms['buyer_confirmed'] === 1 && (int)$confirms['seller_confirmed'] === 1) {
                $pdo->prepare("UPDATE orders   SET status = 'completed', completed_at = NOW() WHERE order_id = ?")->execute([$id]);
                $pdo->prepare("UPDATE listings SET status = 'sold'                            WHERE listing_id = ?")->execute([$order['listing_id']]);
            }
            header('Location: order-confirm.php?id=' . $id);
            exit;
        }
    }
}

// POST: submit review (only after order is completed)
$review_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    require_csrf();
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $review_error = 'Please choose a rating between 1 and 5 stars.';
    } else {
        // Confirm the order is completed and that the user is part of it
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE order_id = ?');
        $stmt->execute([$id]);
        $cur_status = $stmt->fetchColumn();
        if ($cur_status !== 'completed') {
            $review_error = 'You can only review completed orders.';
        } else {
            // Each user can review each order once. Reviewee is the OTHER party.
            $reviewee_id = $is_buyer ? (int)$order['sid'] : (int)$order['bid'];
            $stmt = $pdo->prepare('SELECT review_id FROM reviews WHERE order_id = ? AND reviewer_id = ?');
            $stmt->execute([$id, $uid]);
            if ($stmt->fetch()) {
                $review_error = 'You have already reviewed this order.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO reviews (order_id, reviewer_id, reviewee_id, rating, comment)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$id, $uid, $reviewee_id, $rating, $comment !== '' ? $comment : null]);
                $_SESSION['flash_success'] = 'Thanks — your review has been posted.';
                header('Location: order-confirm.php?id=' . $id);
                exit;
            }
        }
    }
}

/* Re-load to pick up any updates */
$stmt = $pdo->prepare('SELECT status, buyer_confirmed, seller_confirmed FROM orders WHERE order_id = ?');
$stmt->execute([$id]);
$state = $stmt->fetch();

// Review state for the completed view
$my_review     = null;
$their_review  = null;
$reviewee_id   = $is_buyer ? (int)$order['sid'] : (int)$order['bid'];
$reviewee_name = $is_buyer ? $order['seller_name'] : $order['buyer_name'];
if ($state['status'] === 'completed') {
    $stmt = $pdo->prepare('SELECT * FROM reviews WHERE order_id = ? AND reviewer_id = ?');
    $stmt->execute([$id, $uid]);
    $my_review = $stmt->fetch() ?: null;

    $stmt = $pdo->prepare('SELECT * FROM reviews WHERE order_id = ? AND reviewer_id = ?');
    $stmt->execute([$id, $reviewee_id]);
    $their_review = $stmt->fetch() ?: null;
}

$page_title = 'Order #' . $id;
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <?php if ($state['status'] === 'completed'): ?>
        <!-- ============== COMPLETED ============== -->
        <div class="bg-white rounded-lg border border-gray-200 p-8 text-center mb-6">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="check-circle" class="icon-3xl text-green-600"></i>
            </div>
            <h1 class="text-3xl font-bold mb-3">Transaction Complete!</h1>
            <p class="text-gray-600">
                Both parties confirmed the handover. Thank you for using UniMove!
            </p>
        </div>

        <!-- Review section -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="star" class="icon-md" style="color:#fbbf24"></i>
                <h2 class="text-xl font-semibold">
                    <?= $my_review ? 'Your review' : 'Rate your experience' ?>
                </h2>
            </div>

            <?php if ($my_review): ?>
                <p class="text-sm text-gray-600 mb-3">You reviewed <strong><?= e($reviewee_name) ?></strong>:</p>
                <div class="flex items-center gap-1 mb-2">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i data-lucide="star" class="icon-md"
                           style="color:<?= $s <= (int)$my_review['rating'] ? '#fbbf24' : '#d1d5db' ?>; fill:<?= $s <= (int)$my_review['rating'] ? '#fbbf24' : 'transparent' ?>"></i>
                    <?php endfor; ?>
                    <span class="ml-2 text-sm text-gray-600"><?= (int)$my_review['rating'] ?>/5</span>
                </div>
                <?php if (!empty($my_review['comment'])): ?>
                    <p class="text-gray-800 mt-2 italic">"<?= e($my_review['comment']) ?>"</p>
                <?php endif; ?>
                <p class="text-xs text-gray-500 mt-2">Posted <?= e(date('M j, Y', strtotime($my_review['created_at']))) ?></p>

            <?php else: ?>
                <p class="text-sm text-gray-600 mb-4">
                    How was your experience with <strong><?= e($reviewee_name) ?></strong>?
                </p>

                <?php if ($review_error): ?>
                    <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                        <?= e($review_error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="order-confirm.php?id=<?= (int)$id ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="rating" id="ratingInput" value="0">

                    <div class="flex items-center gap-1" id="starRating">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="button" data-star="<?= $s ?>"
                                    class="star-btn p-1 hover:scale-110 transition-transform">
                                <i data-lucide="star" class="icon-xl text-gray-300"></i>
                            </button>
                        <?php endfor; ?>
                        <span id="ratingLabel" class="ml-3 text-sm text-gray-500">Tap to rate</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Comment <span class="text-xs text-gray-500">(optional)</span></label>
                        <textarea name="comment" rows="3" maxlength="1000"
                                  placeholder="Share details about the transaction (item condition, communication, etc.)"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"></textarea>
                    </div>

                    <button type="submit" id="submitReviewBtn" disabled
                            class="bg-pink-600 text-white px-6 py-3 rounded-lg hover:bg-pink-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                        Post review
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($their_review): ?>
                <div class="border-t border-gray-200 mt-6 pt-6">
                    <p class="text-sm text-gray-600 mb-2">
                        <strong><?= e($reviewee_name) ?></strong> reviewed you:
                    </p>
                    <div class="flex items-center gap-1 mb-2">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i data-lucide="star" class="icon-md"
                               style="color:<?= $s <= (int)$their_review['rating'] ? '#fbbf24' : '#d1d5db' ?>; fill:<?= $s <= (int)$their_review['rating'] ? '#fbbf24' : 'transparent' ?>"></i>
                        <?php endfor; ?>
                        <span class="ml-2 text-sm text-gray-600"><?= (int)$their_review['rating'] ?>/5</span>
                    </div>
                    <?php if (!empty($their_review['comment'])): ?>
                        <p class="text-gray-800 mt-2 italic">"<?= e($their_review['comment']) ?>"</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex gap-4 justify-center flex-wrap">
            <a href="dashboard.php" class="px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700">Back to Dashboard</a>
            <a href="browse.php"    class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50">Browse More Items</a>
        </div>

        <script>
        (function () {
            const stars   = document.querySelectorAll('.star-btn');
            const input   = document.getElementById('ratingInput');
            const label   = document.getElementById('ratingLabel');
            const submit  = document.getElementById('submitReviewBtn');
            const labels  = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
            if (!stars.length) return;

            function paint(rating) {
                stars.forEach((btn, i) => {
                    const svg = btn.querySelector('svg, i');
                    if (!svg) return;
                    if (i < rating) {
                        svg.style.color = '#fbbf24';
                        svg.style.fill  = '#fbbf24';
                    } else {
                        svg.style.color = '#d1d5db';
                        svg.style.fill  = 'transparent';
                    }
                });
            }

            stars.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const r = parseInt(btn.dataset.star, 10);
                    input.value = r;
                    label.textContent = labels[r];
                    submit.disabled = false;
                    paint(r);
                });
            });
        })();
        </script>

    <?php else: ?>
        <!-- ============== CONFIRMED — awaiting handover ============== -->
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <i data-lucide="check-circle" class="icon-md text-green-600"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Order Confirmed</h1>
                    <p class="text-sm text-gray-600">Order #<?= (int)$id ?></p>
                </div>
            </div>
            <div class="bg-pink-50 border border-pink-200 rounded-lg p-4">
                <p class="text-sm text-pink-900">
                    Your pickup has been scheduled. Please arrive on time and bring the handover OTP to complete the transaction.
                </p>
            </div>
        </div>

        <!-- Item summary -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="flex gap-6 mb-6 pb-6 border-b border-gray-200">
                <img src="<?= e(listing_image_url($order['image'])) ?>"
                     onerror="this.src='https://placehold.co/400x300?text=No+image'"
                     class="w-32 h-32 object-cover rounded-lg bg-gray-100" alt="">
                <div class="flex-1">
                    <h2 class="text-xl font-bold mb-2"><?= e($order['title']) ?></h2>
                    <div class="text-3xl font-bold text-pink-600 mb-4">R<?= number_format((float)$order['total_price'], 2) ?></div>
                    <div class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                        <?= e(ucfirst($state['status'])) ?>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <?php if ($order['slot_date']): ?>
                    <div class="flex items-start gap-3">
                        <i data-lucide="calendar" class="icon-md text-gray-600 mt-0.5"></i>
                        <div>
                            <div class="font-semibold">Pickup Time</div>
                            <div class="text-gray-600">
                                <?= e(date('l, F j, Y · g:i A', strtotime($order['slot_date'] . ' ' . $order['slot_time']))) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($order['pickup_zone']): ?>
                    <div class="flex items-start gap-3">
                        <i data-lucide="map-pin" class="icon-md text-gray-600 mt-0.5"></i>
                        <div>
                            <div class="font-semibold">Pickup Location</div>
                            <div class="text-gray-600"><?= e($order['pickup_zone']) ?></div>
                            <?php if ($order['pickup_desc']): ?>
                                <div class="text-xs text-gray-500 mt-1"><?= e($order['pickup_desc']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex items-start gap-3">
                    <i data-lucide="user" class="icon-md text-gray-600 mt-0.5"></i>
                    <div>
                        <div class="font-semibold"><?= $is_seller ? 'Buyer' : 'Seller' ?></div>
                        <div class="text-gray-600"><?= e($is_seller ? $order['buyer_name'] : $order['seller_name']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Handover OTP -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="package" class="icon-md text-pink-600"></i>
                <h3 class="text-xl font-semibold">Handover Verification</h3>
            </div>

            <!-- Your OTP — share with the OTHER party -->
            <div class="mb-6">
                <p class="text-gray-600 mb-3 text-sm">
                    Share this code with the <?= $is_seller ? 'buyer' : 'seller' ?> when you meet for the handover.
                </p>
                <div class="bg-gray-50 rounded-lg p-6 text-center">
                    <div class="text-sm text-gray-600 mb-2">Your Handover OTP</div>
                    <div class="text-4xl font-bold text-pink-600 tracking-widest font-mono">
                        <?= e($is_seller ? $order['seller_otp'] : $order['buyer_otp']) ?>
                    </div>
                </div>
            </div>

            <!-- Enter the OTHER party's OTP -->
            <div>
                <p class="text-gray-600 mb-4 text-sm">
                    Ask the <?= $is_seller ? 'buyer' : 'seller' ?> for THEIR code and enter it below to confirm the handover from your side.
                </p>

                <?php $already = ($is_buyer ? (int)$state['buyer_confirmed'] : (int)$state['seller_confirmed']) === 1; ?>

                <?php if ($already): ?>
                    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 text-sm">
                        You've confirmed your side of the handover. Waiting on the other party to confirm.
                    </div>
                <?php else: ?>
                    <?php if ($otp_error): ?>
                        <div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                            <?= e($otp_error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="order-confirm.php?id=<?= (int)$id ?>" class="space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="verify_otp">
                        <input type="hidden" name="otp" id="otpHidden">

                        <div class="um-otp-boxes flex gap-2 justify-center" data-otp-target="#otpHidden">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <input type="text" inputmode="numeric" maxlength="1"
                                       class="um-otp-cell w-12 h-14 border-2 border-gray-300 rounded-lg text-center text-2xl font-semibold focus:outline-none focus:border-pink-500 transition-colors">
                            <?php endfor; ?>
                        </div>

                        <button type="submit"
                                class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors">
                            Verify &amp; Confirm Handover
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="messages.php?with=<?= e($is_seller ? (int)$order['bid'] : (int)$order['sid']) ?>&listing=<?= (int)$order['listing_id'] ?>"
               class="text-pink-600 hover:text-pink-700">
                Message <?= $is_seller ? 'buyer' : 'seller' ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
