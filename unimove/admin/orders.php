<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin', 'moderator'], 'index.php');

$status_f = $_GET['status'] ?? 'all';
$q        = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if (in_array($status_f, ['pending', 'confirmed', 'completed', 'cancelled', 'disputed'], true)) {
    $where[] = 'o.status = ?'; $params[] = $status_f;
}
if ($q !== '') {
    $where[] = '(l.title LIKE ? OR ub.full_name LIKE ? OR us.full_name LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}

$sql = "SELECT o.*, l.title,
               ub.full_name AS buyer_name, us.full_name AS seller_name,
               t.slot_date, t.slot_time, pz.name AS pickup_zone
          FROM orders o
          JOIN listings l        ON l.listing_id = o.listing_id
          JOIN users ub          ON ub.user_id = o.buyer_id
          JOIN users us          ON us.user_id = o.seller_id
     LEFT JOIN timeslots t       ON t.slot_id = o.slot_id
     LEFT JOIN pickup_zones pz   ON pz.zone_id = t.zone_id
         WHERE " . implode(' AND ', $where) . "
      ORDER BY o.created_at DESC
         LIMIT 200";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$orders = $stmt->fetchAll();

$totals = $pdo->query(
    "SELECT
       SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) AS pending,
       SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
       SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
       SUM(CASE WHEN status = 'disputed'  THEN 1 ELSE 0 END) AS disputed,
       SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) AS gmv
     FROM orders"
)->fetch();

$page_title       = 'Orders';
$active_admin_nav = 'orders';
include __DIR__ . '/_layout.php';
?>

<h1 class="text-2xl font-bold mb-6">Orders</h1>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <?php
    $cells = [
        ['Pending',   $totals['pending'],   'text-yellow-700', 'bg-yellow-50'],
        ['Confirmed', $totals['confirmed'], 'text-green-700',  'bg-green-50'],
        ['Completed', $totals['completed'], 'text-gray-700',   'bg-gray-50'],
        ['Disputed',  $totals['disputed'],  'text-orange-700', 'bg-orange-50'],
        ['GMV',       'R' . number_format((float)$totals['gmv'], 2), 'text-pink-700', 'bg-pink-50'],
    ];
    foreach ($cells as [$lbl, $val, $col, $bg]):
    ?>
        <div class="<?= $bg ?> rounded-lg p-4">
            <div class="text-xs uppercase tracking-wide text-gray-500"><?= e($lbl) ?></div>
            <div class="text-xl font-bold <?= $col ?>"><?= is_numeric($val) ? number_format((int)$val) : e($val) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<form method="GET" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs text-gray-500 mb-1">Search</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Item, buyer, seller…"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Status</label>
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <?php foreach (['all', 'pending', 'confirmed', 'completed', 'cancelled', 'disputed'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $status_f === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 text-sm">Apply</button>
</form>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Buyer</th>
                    <th class="px-4 py-3">Seller</th>
                    <th class="px-4 py-3">Pickup</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($orders as $o):
                    $cls = [
                        'pending'   => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-green-100 text-green-700',
                        'completed' => 'bg-gray-100 text-gray-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        'disputed'  => 'bg-orange-100 text-orange-700',
                    ][$o['status']] ?? 'bg-gray-100 text-gray-700';
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">#<?= (int)$o['order_id'] ?></td>
                        <td class="px-4 py-3"><?= e($o['title']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= e($o['buyer_name']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= e($o['seller_name']) ?></td>
                        <td class="px-4 py-3 text-gray-700 text-xs">
                            <?php if ($o['slot_date']): ?>
                                <?= e(date('M j, Y g:i A', strtotime($o['slot_date'] . ' ' . $o['slot_time']))) ?><br>
                                <span class="text-gray-500"><?= e($o['pickup_zone'] ?? '—') ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-semibold text-pink-600">R<?= number_format((float)$o['total_price'], 2) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs <?= $cls ?>"><?= e($o['status']) ?></span></td>
                        <td class="px-4 py-3 text-xs text-gray-500"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
