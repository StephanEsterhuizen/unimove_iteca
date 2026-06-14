<?php
$page_title       = 'Admin Dashboard';
$active_admin_nav = 'dashboard';
include __DIR__ . '/_layout.php';

// KPIs
$kpi = [
    'users'           => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'listings_total'  => (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status != 'removed'")->fetchColumn(),
    'listings_pending'=> (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'pending'")->fetchColumn(),
    'listings_active' => (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
    'orders_completed'=> (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
    'reports_open'    => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn(),
];

// Recent activity
$recent_listings = $pdo->query(
    "SELECT l.listing_id, l.title, l.status, l.created_at, u.full_name AS seller_name
       FROM listings l
       JOIN users u ON u.user_id = l.seller_id
   ORDER BY l.created_at DESC
      LIMIT 8"
)->fetchAll();

$recent_users = $pdo->query(
    "SELECT user_id, full_name, email, role, is_verified, created_at
       FROM users
   ORDER BY created_at DESC
      LIMIT 8"
)->fetchAll();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold">Admin Dashboard</h1>
        <p class="text-gray-600 text-sm">Welcome back, <?= e(current_user()['full_name']) ?>.</p>
    </div>
    <div class="flex gap-2">
        <?php if ($kpi['listings_pending'] > 0): ?>
            <a href="listings.php?status=pending"
               class="bg-yellow-100 text-yellow-800 px-3 py-2 rounded-lg text-sm font-medium hover:bg-yellow-200">
                <?= $kpi['listings_pending'] ?> pending listing<?= $kpi['listings_pending'] === 1 ? '' : 's' ?>
            </a>
        <?php endif; ?>
        <?php if ($kpi['reports_open'] > 0): ?>
            <a href="reports.php"
               class="bg-red-100 text-red-800 px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-200">
                <?= $kpi['reports_open'] ?> open report<?= $kpi['reports_open'] === 1 ? '' : 's' ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- KPI cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
        ['Users',             $kpi['users'],            'users',           'text-blue-600'],
        ['Active listings',   $kpi['listings_active'],  'package',         'text-pink-600'],
        ['Completed orders',  $kpi['orders_completed'], 'shopping-bag',    'text-green-600'],
        ['Open reports',      $kpi['reports_open'],     'flag',            'text-red-600'],
    ];
    foreach ($cards as [$lbl, $val, $icon, $col]):
    ?>
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold <?= $col ?>"><?= number_format((int)$val) ?></div>
                    <div class="text-sm text-gray-600"><?= e($lbl) ?></div>
                </div>
                <i data-lucide="<?= e($icon) ?>" class="icon-2xl <?= $col ?> opacity-30"></i>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Recent listings -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h2 class="font-semibold">Recent listings</h2>
            <a href="listings.php" class="text-sm text-pink-600 hover:text-pink-700">View all →</a>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($recent_listings as $l):
                $cls = [
                    'pending'  => 'bg-yellow-100 text-yellow-800',
                    'active'   => 'bg-green-100 text-green-700',
                    'sold'     => 'bg-gray-100 text-gray-700',
                    'flagged'  => 'bg-red-100 text-red-700',
                    'removed'  => 'bg-gray-800 text-white',
                ][$l['status']] ?? 'bg-gray-100 text-gray-700';
            ?>
                <a href="../listing.php?id=<?= (int)$l['listing_id'] ?>"
                   class="flex items-center gap-3 p-3 hover:bg-gray-50">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate"><?= e($l['title']) ?></div>
                        <div class="text-xs text-gray-500"><?= e($l['seller_name']) ?> · <?= e(date('M j, g:i A', strtotime($l['created_at']))) ?></div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-xs <?= $cls ?>"><?= e($l['status']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (empty($recent_listings)): ?>
                <div class="p-6 text-gray-500 text-sm">No listings yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent users -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h2 class="font-semibold">Recent sign-ups</h2>
            <a href="users.php" class="text-sm text-pink-600 hover:text-pink-700">View all →</a>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach ($recent_users as $u): ?>
                <div class="flex items-center gap-3 p-3 hover:bg-gray-50">
                    <div class="w-9 h-9 rounded-full bg-pink-600 text-white text-sm font-semibold flex items-center justify-center flex-shrink-0">
                        <?= e(mb_substr($u['full_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium flex items-center gap-1">
                            <?= e($u['full_name']) ?>
                            <?php if ((int)$u['is_verified'] === 1): ?>
                                <i data-lucide="badge-check" class="icon-xs text-blue-600"></i>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-500 truncate"><?= e($u['email']) ?></div>
                    </div>
                    <span class="text-xs text-gray-500"><?= e(date('M j', strtotime($u['created_at']))) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recent_users)): ?>
                <div class="p-6 text-gray-500 text-sm">No users yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
