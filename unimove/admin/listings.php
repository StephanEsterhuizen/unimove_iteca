<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin', 'moderator'], 'index.php');

$is_admin_role = current_role() === 'admin';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action     = $_POST['action'] ?? '';
    $listing_id = (int)($_POST['listing_id'] ?? 0);

    if ($listing_id > 0) {
        // ---- Hard delete (admin only) ----
        if ($action === 'destroy') {
            if (!$is_admin_role) {
                $_SESSION['flash_error'] = 'Only admins can permanently delete listings.';
            } else {
                // Refuse to delete if any order references it — preserve audit trail.
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE listing_id = ?');
                $stmt->execute([$listing_id]);
                $order_count = (int)$stmt->fetchColumn();

                if ($order_count > 0) {
                    $_SESSION['flash_error'] = "Cannot permanently delete: this listing has $order_count order(s) tied to it. Use 'Remove' to soft-delete instead so the transaction history stays intact.";
                } else {
                    try {
                        $pdo->beginTransaction();
                        // Detach messages + reports first (they have nullable listing_id FKs)
                        $pdo->prepare('UPDATE messages SET listing_id = NULL WHERE listing_id = ?')->execute([$listing_id]);
                        $pdo->prepare('UPDATE reports  SET listing_id = NULL WHERE listing_id = ?')->execute([$listing_id]);
                        // listing_images cascade automatically via FK
                        $pdo->prepare('DELETE FROM listings WHERE listing_id = ?')->execute([$listing_id]);
                        $pdo->commit();
                        $_SESSION['flash_success'] = 'Listing permanently deleted.';
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        $_SESSION['flash_error'] = 'Delete failed: ' . $e->getMessage();
                    }
                }
            }
        } else {
            // ---- Status-change actions ----
            $new_status = [
                'approve' => 'active',
                'flag'    => 'flagged',
                'remove'  => 'removed',
                'restore' => 'active',
            ][$action] ?? null;
            if ($new_status) {
                $pdo->prepare('UPDATE listings SET status = ? WHERE listing_id = ?')->execute([$new_status, $listing_id]);
                $_SESSION['flash_success'] = 'Listing status set to ' . $new_status . '.';
            }
        }
    }
    header('Location: listings.php?' . http_build_query($_GET));
    exit;
}

// Filters
$status_f = $_GET['status'] ?? 'all';
$q        = trim($_GET['q'] ?? '');

$where  = ["l.status != 'removed' OR ? = 'removed'"];
$params = [$status_f];

if (in_array($status_f, ['pending', 'active', 'sold', 'flagged', 'removed'], true)) {
    $where[]  = 'l.status = ?';
    $params[] = $status_f;
}
if ($q !== '') {
    $where[] = '(l.title LIKE ? OR u.full_name LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}

$sql = "SELECT l.*, c.name AS category, u.full_name AS seller_name,
               (SELECT image_path FROM listing_images
                 WHERE listing_id = l.listing_id
              ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image
          FROM listings l
          JOIN categories c ON c.category_id = l.category_id
          JOIN users u      ON u.user_id = l.seller_id
         WHERE " . implode(' AND ', $where) . "
      ORDER BY l.created_at DESC
         LIMIT 200";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$listings = $stmt->fetchAll();

$page_title       = 'Listings';
$active_admin_nav = 'listings';
include __DIR__ . '/_layout.php';
?>

<h1 class="text-2xl font-bold mb-6">Listings</h1>

<!-- Status tabs -->
<div class="flex gap-2 mb-6 flex-wrap">
    <?php
    $tabs = ['all' => 'All', 'pending' => 'Pending', 'active' => 'Active', 'flagged' => 'Flagged', 'sold' => 'Sold', 'removed' => 'Removed'];
    foreach ($tabs as $key => $lbl):
        $active = $status_f === $key;
    ?>
        <a href="?status=<?= e($key) ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
           class="px-3 py-1.5 rounded-full text-sm border <?= $active ? 'bg-pink-600 text-white border-pink-600' : 'bg-white border-gray-300 hover:bg-gray-50' ?>">
            <?= e($lbl) ?>
        </a>
    <?php endforeach; ?>
</div>

<form method="GET" action="listings.php" class="mb-6">
    <input type="hidden" name="status" value="<?= e($status_f) ?>">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by title or seller…"
           class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
</form>

<!-- Table -->
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">Listing</th>
                    <th class="px-4 py-3">Seller</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($listings as $l):
                    $cls = [
                        'pending'  => 'bg-yellow-100 text-yellow-800',
                        'active'   => 'bg-green-100 text-green-700',
                        'sold'     => 'bg-gray-100 text-gray-700',
                        'flagged'  => 'bg-red-100 text-red-700',
                        'removed'  => 'bg-gray-800 text-white',
                    ][$l['status']] ?? 'bg-gray-100 text-gray-700';
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="<?= e(listing_image_url($l['image'])) ?>"
                                     onerror="this.src='https://placehold.co/80x80?text=•'"
                                     class="w-12 h-12 object-cover rounded bg-gray-100" alt="">
                                <div>
                                    <a href="../listing.php?id=<?= (int)$l['listing_id'] ?>" target="_blank"
                                       class="font-medium hover:text-pink-600"><?= e($l['title']) ?></a>
                                    <div class="text-xs text-gray-500">#<?= (int)$l['listing_id'] ?> · <?= e($l['condition_type']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-700"><?= e($l['seller_name']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= e($l['category']) ?></td>
                        <td class="px-4 py-3 font-semibold text-pink-600">R<?= number_format((float)$l['price'], 2) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs <?= $cls ?>"><?= e($l['status']) ?></span></td>
                        <td class="px-4 py-3 text-xs text-gray-500"><?= e(date('M j, Y', strtotime($l['created_at']))) ?></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2 flex-wrap">
                                <?php if ($l['status'] === 'pending' || $l['status'] === 'flagged'): ?>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action"     value="approve">
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                                        <button class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Approve</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($l['status'], ['active', 'pending'], true)): ?>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action"     value="flag">
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                                        <button class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200">Flag</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($l['status'] !== 'removed'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Soft-remove this listing? The seller will no longer see it and it disappears from the marketplace, but the row is kept for audit.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action"     value="remove">
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                                        <button class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200">Remove</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action"     value="restore">
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                                        <button class="text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Restore</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($is_admin_role): ?>
                                    <form method="POST" class="inline"
                                          onsubmit="return confirm('PERMANENTLY DELETE this listing and all its images?\n\nThis action cannot be undone. Listings with order history cannot be hard-deleted (use Remove instead).');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action"     value="destroy">
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                                        <button class="text-xs px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700" title="Permanently delete">
                                            <i data-lucide="trash-2" class="icon-xs"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($listings)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No listings match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
