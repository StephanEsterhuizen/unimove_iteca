<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$uid = current_user_id();
$tab = $_GET['tab'] ?? 'listings';
if (!in_array($tab, ['listings', 'orders', 'messages'], true)) $tab = 'listings';

// Delete listing (own only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_csrf();
    $del_id = (int)($_POST['listing_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE listings SET status = 'removed' WHERE listing_id = ? AND seller_id = ?");
    $stmt->execute([$del_id, $uid]);
    $_SESSION['flash_success'] = 'Listing removed.';
    header('Location: dashboard.php?tab=listings');
    exit;
}

// My listings
$stmt = $pdo->prepare(
    "SELECT l.*, c.name AS category,
            (SELECT image_path FROM listing_images
              WHERE listing_id = l.listing_id
           ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image,
            (SELECT COUNT(*) FROM messages m WHERE m.listing_id = l.listing_id AND m.receiver_id = ?) AS message_count
       FROM listings l
       JOIN categories c ON c.category_id = l.category_id
      WHERE l.seller_id = ? AND l.status != 'removed'
   ORDER BY l.created_at DESC"
);
$stmt->execute([$uid, $uid]);
$my_listings = $stmt->fetchAll();

// My orders (buying + selling)
$stmt = $pdo->prepare(
    "SELECT o.*, l.title, l.price AS listing_price,
            (SELECT image_path FROM listing_images
              WHERE listing_id = l.listing_id
           ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image,
            ub.full_name AS buyer_name, us.full_name AS seller_name,
            t.slot_date, t.slot_time,
            pz.name AS pickup_zone,
            CASE WHEN o.buyer_id = ? THEN 'buying' ELSE 'selling' END AS type
       FROM orders o
       JOIN listings l        ON l.listing_id = o.listing_id
       JOIN users ub          ON ub.user_id = o.buyer_id
       JOIN users us          ON us.user_id = o.seller_id
  LEFT JOIN timeslots t       ON t.slot_id = o.slot_id
  LEFT JOIN pickup_zones pz   ON pz.zone_id = t.zone_id
      WHERE (o.buyer_id = ? OR o.seller_id = ?)
   ORDER BY o.created_at DESC"
);
$stmt->execute([$uid, $uid, $uid]);
$orders = $stmt->fetchAll();

// Recent messages
$stmt = $pdo->prepare(
    "SELECT m.*, u.full_name AS sender_name, l.title AS listing_title
       FROM messages m
       JOIN users u    ON u.user_id = m.sender_id
  LEFT JOIN listings l ON l.listing_id = m.listing_id
      WHERE m.receiver_id = ?
   ORDER BY m.sent_at DESC
      LIMIT 20"
);
$stmt->execute([$uid]);
$messages = $stmt->fetchAll();

$active_count    = count(array_filter($my_listings, fn($l) => $l['status'] === 'active'));
$pending_count   = count(array_filter($my_listings, fn($l) => $l['status'] === 'pending'));
$active_orders   = count(array_filter($orders,     fn($o) => in_array($o['status'], ['pending', 'confirmed'], true)));
$unread_msgs     = count(array_filter($messages,   fn($m) => (int)$m['is_read'] === 0));

$page_title = 'My Dashboard';
$active_nav = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">My Dashboard</h1>
        <p class="text-gray-600">Manage your listings, orders, and messages</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-pink-600"><?= $active_count ?></div>
                    <div class="text-sm text-gray-600">Active Listings</div>
                    <?php if ($pending_count > 0): ?>
                        <div class="text-xs text-yellow-700 mt-1"><?= $pending_count ?> pending approval</div>
                    <?php endif; ?>
                </div>
                <i data-lucide="package" class="icon-3xl text-pink-600 opacity-20"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-green-600"><?= $active_orders ?></div>
                    <div class="text-sm text-gray-600">Active Orders</div>
                </div>
                <i data-lucide="shopping-bag" class="icon-3xl text-green-600 opacity-20"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-purple-600"><?= $unread_msgs ?></div>
                    <div class="text-sm text-gray-600">Unread Messages</div>
                </div>
                <i data-lucide="message-square" class="icon-3xl text-purple-600 opacity-20"></i>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="border-b border-gray-200">
            <div class="flex">
                <?php
                $tab_link = function (string $key, string $label, int $badge = 0) use ($tab) {
                    $is_active = $tab === $key;
                    $cls = $is_active ? 'border-pink-600 text-pink-600' : 'border-transparent text-gray-600 hover:text-gray-900';
                    echo '<a href="dashboard.php?tab=' . urlencode($key) . '" class="px-6 py-4 font-medium border-b-2 transition-colors relative ' . $cls . '">' . htmlspecialchars($label);
                    if ($badge > 0) {
                        echo '<span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">' . $badge . '</span>';
                    }
                    echo '</a>';
                };
                $tab_link('listings', 'My Listings');
                $tab_link('orders',   'Orders');
                $tab_link('messages', 'Messages', $unread_msgs);
                ?>
            </div>
        </div>

        <div class="p-6">

            <?php if ($tab === 'listings'): ?>
                <!-- ============ My Listings ============ -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold">Your Listings</h2>
                    <a href="create-listing.php"
                       class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition-colors">
                        Create New Listing
                    </a>
                </div>

                <?php if (empty($my_listings)): ?>
                    <p class="text-gray-500 py-8 text-center">You haven't created any listings yet.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($my_listings as $l):
                            $status_cls = [
                                'pending'  => 'bg-yellow-100 text-yellow-700',
                                'active'   => 'bg-green-100 text-green-700',
                                'sold'     => 'bg-gray-100 text-gray-700',
                                'flagged'  => 'bg-red-100 text-red-700',
                                'removed'  => 'bg-gray-800 text-white',
                            ][$l['status']] ?? 'bg-gray-100 text-gray-700';
                        ?>
                            <div class="flex gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                                <img src="<?= e(listing_image_url($l['image'])) ?>"
                                     onerror="this.src='https://placehold.co/200x150?text=No+image'"
                                     class="w-24 h-24 object-cover rounded-lg bg-gray-100" alt="">
                                <div class="flex-1">
                                    <h3 class="font-semibold mb-1"><?= e($l['title']) ?></h3>
                                    <div class="flex items-center gap-3 text-sm text-gray-600 mb-2 flex-wrap">
                                        <span class="font-bold text-pink-600">R<?= number_format((float)$l['price'], 2) ?></span>
                                        <span>•</span>
                                        <span><?= e($l['condition_type']) ?></span>
                                        <span>•</span>
                                        <span class="px-2 py-0.5 rounded text-xs <?= $status_cls ?>"><?= e($l['status']) ?></span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="message-square" class="icon-sm"></i>
                                            <?= (int)$l['message_count'] ?> messages
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <a href="listing.php?id=<?= (int)$l['listing_id'] ?>"
                                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm flex items-center gap-2">
                                        <i data-lucide="eye" class="icon-sm"></i> View
                                    </a>
                                    <a href="edit-listing.php?id=<?= (int)$l['listing_id'] ?>"
                                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm flex items-center gap-2">
                                        <i data-lucide="edit" class="icon-sm"></i> Edit
                                    </a>
                                    <form method="POST" action="dashboard.php?tab=listings"
                                          onsubmit="return confirm('Remove this listing?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                                        <button type="submit"
                                                class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors text-sm flex items-center gap-2">
                                            <i data-lucide="trash-2" class="icon-sm"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'orders'): ?>
                <!-- ============ Orders ============ -->
                <h2 class="text-xl font-semibold mb-6">Your Orders</h2>

                <?php if (empty($orders)): ?>
                    <p class="text-gray-500 py-8 text-center">No orders yet.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($orders as $o):
                            $type_cls   = $o['type'] === 'buying' ? 'bg-pink-100 text-pink-700' : 'bg-green-100 text-green-700';
                            $status_cls = [
                                'pending'   => 'bg-yellow-100 text-yellow-700',
                                'confirmed' => 'bg-green-100 text-green-700',
                                'completed' => 'bg-gray-100 text-gray-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                'disputed'  => 'bg-orange-100 text-orange-700',
                            ][$o['status']] ?? 'bg-gray-100 text-gray-700';
                        ?>
                            <div class="p-4 border border-gray-200 rounded-lg">
                                <div class="flex gap-4">
                                    <img src="<?= e(listing_image_url($o['image'])) ?>"
                                         onerror="this.src='https://placehold.co/200x150?text=No+image'"
                                         class="w-24 h-24 object-cover rounded-lg bg-gray-100" alt="">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                                            <span class="text-xs px-2 py-1 rounded <?= $type_cls ?>">
                                                <?= ucfirst($o['type']) ?>
                                            </span>
                                            <span class="text-xs px-2 py-1 rounded <?= $status_cls ?>">
                                                <?= e($o['status']) ?>
                                            </span>
                                        </div>
                                        <h3 class="font-semibold mb-1"><?= e($o['title']) ?></h3>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <?= $o['type'] === 'buying'
                                                ? 'Seller: ' . e($o['seller_name'])
                                                : 'Buyer: '  . e($o['buyer_name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <?php if ($o['slot_date']): ?>
                                                <div>Pickup: <?= e(date('M j, Y g:i A', strtotime($o['slot_date'] . ' ' . $o['slot_time']))) ?></div>
                                            <?php endif; ?>
                                            <?php if ($o['pickup_zone']): ?>
                                                <div>Location: <?= e($o['pickup_zone']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col justify-between">
                                        <div class="text-xl font-bold text-pink-600 mb-4">R<?= number_format((float)$o['total_price'], 2) ?></div>
                                        <a href="order-confirm.php?id=<?= (int)$o['order_id'] ?>"
                                           class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors text-sm whitespace-nowrap">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php else: /* messages tab */ ?>
                <!-- ============ Messages ============ -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold">Recent Messages</h2>
                    <a href="messages.php" class="text-pink-600 hover:text-pink-700 font-medium">View all →</a>
                </div>

                <?php if (empty($messages)): ?>
                    <p class="text-gray-500 py-8 text-center">No messages yet.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($messages as $m):
                            $cls = (int)$m['is_read'] === 0
                                ? 'bg-pink-50 border-pink-200 hover:bg-pink-100'
                                : 'bg-white border-gray-200 hover:bg-gray-50';
                        ?>
                            <a href="messages.php?with=<?= (int)$m['sender_id'] ?>&listing=<?= (int)($m['listing_id'] ?? 0) ?>"
                               class="block p-4 rounded-lg border transition-colors <?= $cls ?>">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold"><?= e($m['sender_name']) ?></span>
                                    <span class="text-xs text-gray-500"><?= e(date('M j, g:i A', strtotime($m['sent_at']))) ?></span>
                                </div>
                                <?php if ($m['listing_title']): ?>
                                    <div class="text-sm text-gray-600 mb-1"><?= e($m['listing_title']) ?></div>
                                <?php endif; ?>
                                <div class="text-sm text-gray-700"><?= e(mb_strimwidth($m['message_text'], 0, 120, '…')) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
