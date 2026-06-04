<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$pid = (int)($_GET['id'] ?? 0);
if ($pid <= 0) { http_response_code(404); header('Location: 404.php'); exit; }

$stmt = $pdo->prepare(
    'SELECT user_id, full_name, university, is_verified, profile_pic, created_at
       FROM users WHERE user_id = ? AND is_suspended = 0'
);
$stmt->execute([$pid]);
$user = $stmt->fetch();
if (!$user) { http_response_code(404); header('Location: 404.php'); exit; }

/* ---- POST: report this user ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'report_user') {
    require_csrf();
    require_login();

    $reason = trim($_POST['reason'] ?? '');
    if (current_user_id() === $pid) {
        $_SESSION['flash_error'] = 'You cannot report yourself.';
    } elseif (mb_strlen($reason) < 10) {
        $_SESSION['flash_error'] = 'Please give a reason of at least 10 characters.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT report_id FROM reports
              WHERE reporter_id = ? AND user_id = ? AND status IN ('open','reviewed')"
        );
        $stmt->execute([current_user_id(), $pid]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'You have already reported this user — our moderators are looking into it.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO reports (reporter_id, user_id, reason, status)
                 VALUES (?, ?, ?, "open")'
            );
            $stmt->execute([current_user_id(), $pid, $reason]);
            $_SESSION['flash_success'] = 'Report submitted. A moderator will review it soon.';
        }
    }
    header('Location: profile.php?id=' . $pid);
    exit;
}

/* ---- Active listings ---- */
$stmt = $pdo->prepare(
    "SELECT l.listing_id, l.title, l.price, l.condition_type, l.status,
            (SELECT image_path FROM listing_images
              WHERE listing_id = l.listing_id
           ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image
       FROM listings l
      WHERE l.seller_id = ? AND l.status IN ('active', 'sold')
   ORDER BY l.created_at DESC"
);
$stmt->execute([$pid]);
$listings = $stmt->fetchAll();

$active_listings = array_filter($listings, fn($l) => $l['status'] === 'active');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = "completed"');
$stmt->execute([$pid]);
$total_sales = (int)$stmt->fetchColumn();

/* ---- Reviews ---- */
$stmt = $pdo->prepare(
    "SELECT r.*, u.full_name AS reviewer_name,
            l.title AS listing_title
       FROM reviews r
       JOIN users u    ON u.user_id = r.reviewer_id
  LEFT JOIN orders o   ON o.order_id = r.order_id
  LEFT JOIN listings l ON l.listing_id = o.listing_id
      WHERE r.reviewee_id = ?
   ORDER BY r.created_at DESC"
);
$stmt->execute([$pid]);
$reviews = $stmt->fetchAll();

$rating_count = count($reviews);
$avg_rating   = $rating_count > 0
    ? round(array_sum(array_column($reviews, 'rating')) / $rating_count, 1)
    : 0;

$rating_distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $r) { $rating_distribution[(int)$r['rating']]++; }

$is_own_profile = is_logged_in() && current_user_id() === $pid;

$page_title = $user['full_name'];
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- ============== Sidebar ============== -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-gray-200 p-6 sticky top-24">
                <div class="text-center mb-6">
                    <div class="w-24 h-24 bg-pink-600 rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4">
                        <?= e(mb_substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <h1 class="text-2xl font-bold mb-1 flex items-center justify-center gap-1">
                        <?= e($user['full_name']) ?>
                        <?php if ((int)$user['is_verified'] === 1): ?>
                            <i data-lucide="badge-check" class="icon-md text-blue-600" title="Verified"></i>
                        <?php endif; ?>
                    </h1>

                    <?php if ($rating_count > 0): ?>
                        <div class="flex items-center justify-center gap-1 mb-2">
                            <i data-lucide="star" class="icon-md" style="color:#fbbf24; fill:#fbbf24"></i>
                            <span class="text-xl font-semibold"><?= e((string)$avg_rating) ?></span>
                            <span class="text-gray-600">(<?= $rating_count ?> reviews)</span>
                        </div>
                    <?php else: ?>
                        <div class="text-sm text-gray-500 mb-2">No reviews yet</div>
                    <?php endif; ?>

                    <div class="flex items-center justify-center gap-1 text-sm text-gray-600">
                        <i data-lucide="calendar" class="icon-sm"></i>
                        Member since <?= e(date('F Y', strtotime($user['created_at']))) ?>
                    </div>

                    <?php if (!empty($user['university'])): ?>
                        <div class="flex items-center justify-center gap-1 text-sm text-gray-600 mt-1">
                            <i data-lucide="graduation-cap" class="icon-sm"></i>
                            <?= e($user['university']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-pink-600"><?= count($active_listings) ?></div>
                        <div class="text-sm text-gray-600">Active Listings</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?= $total_sales ?></div>
                        <div class="text-sm text-gray-600">Total Sales</div>
                    </div>
                </div>

                <?php if (!$is_own_profile && is_logged_in()): ?>
                    <a href="messages.php?with=<?= (int)$pid ?>"
                       class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="message-square" class="icon-md"></i>
                        Send Message
                    </a>
                    <button type="button" id="openReportUser"
                            class="w-full mt-2 text-sm text-gray-500 hover:text-red-600 inline-flex items-center justify-center gap-1">
                        <i data-lucide="flag" class="icon-sm"></i>
                        Report this user
                    </button>
                <?php elseif (!is_logged_in()): ?>
                    <a href="login.php" class="block text-center text-pink-600 hover:text-pink-700">
                        Log in to message this user
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============== Main column ============== -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Active listings -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-6">
                    <i data-lucide="package" class="icon-md text-pink-600"></i>
                    <h2 class="text-xl font-bold">Active Listings</h2>
                </div>

                <?php if (empty($active_listings)): ?>
                    <p class="text-gray-500">No active listings.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($active_listings as $l): ?>
                            <a href="listing.php?id=<?= (int)$l['listing_id'] ?>"
                               class="bg-white rounded-lg overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow">
                                <img src="<?= e(listing_image_url($l['image'])) ?>"
                                     onerror="this.src='https://placehold.co/400x300?text=No+image'"
                                     class="w-full h-40 object-cover bg-gray-100" alt="">
                                <div class="p-4">
                                    <h3 class="font-semibold mb-2 truncate"><?= e($l['title']) ?></h3>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xl font-bold text-pink-600">R<?= number_format((float)$l['price'], 2) ?></span>
                                        <span class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                            <?= e($l['condition_type']) ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reviews -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center gap-2 mb-6">
                    <i data-lucide="star" class="icon-md" style="color:#fbbf24"></i>
                    <h2 class="text-xl font-bold">Reviews (<?= $rating_count ?>)</h2>
                </div>

                <?php if ($rating_count > 0): ?>
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <div class="flex items-center gap-4">
                            <div class="text-center">
                                <div class="text-4xl font-bold text-pink-600"><?= e((string)$avg_rating) ?></div>
                                <div class="flex items-center gap-1 mt-1">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i data-lucide="star" class="icon-sm"
                                           style="color:<?= $s <= round($avg_rating) ? '#fbbf24' : '#d1d5db' ?>; fill:<?= $s <= round($avg_rating) ? '#fbbf24' : 'transparent' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-sm text-gray-600 mt-1"><?= $rating_count ?> reviews</div>
                            </div>
                            <div class="flex-1 space-y-2">
                                <?php for ($r = 5; $r >= 1; $r--):
                                    $count = $rating_distribution[$r];
                                    $pct   = $rating_count ? ($count / $rating_count) * 100 : 0;
                                ?>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-600 w-8"><?= $r ?>★</span>
                                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full" style="width: <?= e((string)$pct) ?>%; background:#fbbf24;"></div>
                                        </div>
                                        <span class="text-sm text-gray-600 w-8"><?= $count ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="border-b border-gray-200 pb-4 last:border-0">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-semibold flex-shrink-0">
                                        <?= e(mb_substr($rev['reviewer_name'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-semibold"><?= e($rev['reviewer_name']) ?></span>
                                            <span class="text-sm text-gray-500"><?= e(date('M j, Y', strtotime($rev['created_at']))) ?></span>
                                        </div>
                                        <div class="flex items-center gap-1 mb-2">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <i data-lucide="star" class="icon-sm"
                                                   style="color:<?= $s <= (int)$rev['rating'] ? '#fbbf24' : '#d1d5db' ?>; fill:<?= $s <= (int)$rev['rating'] ? '#fbbf24' : 'transparent' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if (!empty($rev['comment'])): ?>
                                            <p class="text-gray-700 mb-1"><?= e($rev['comment']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($rev['listing_title'])): ?>
                                            <p class="text-sm text-gray-500">Item: <?= e($rev['listing_title']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">No reviews yet. Reviews appear here once buyers complete a transaction with this user.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============ Report User Modal ============ -->
<?php if (!$is_own_profile && is_logged_in()): ?>
<div id="reportUserModal"
     class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="flag" class="icon-md text-red-600"></i>
                Report this user
            </h3>
            <button type="button" id="closeReportUser" class="text-gray-400 hover:text-gray-700">
                <i data-lucide="x" class="icon-md"></i>
            </button>
        </div>

        <p class="text-sm text-gray-600 mb-4">
            Tell our moderators what's wrong with <strong><?= e($user['full_name']) ?></strong>'s behaviour.
            Reports are confidential — they will not see who reported them.
        </p>

        <form method="POST" action="profile.php?id=<?= (int)$pid ?>" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="report_user">

            <div>
                <label class="block text-sm font-medium mb-2">Reason</label>
                <textarea name="reason" required minlength="10" rows="4" maxlength="1000"
                          placeholder="e.g. They tried to scam me, refused to show up, abusive language, asked to meet off-campus…"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm"></textarea>
                <p class="text-xs text-gray-500 mt-1">Minimum 10 characters.</p>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" id="cancelReportUser"
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

<script>
(function () {
    const openBtn   = document.getElementById('openReportUser');
    const closeBtn  = document.getElementById('closeReportUser');
    const cancelBtn = document.getElementById('cancelReportUser');
    const modal     = document.getElementById('reportUserModal');
    if (!openBtn || !modal) return;
    const open  = () => modal.classList.remove('hidden');
    const close = () => modal.classList.add('hidden');
    openBtn.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
