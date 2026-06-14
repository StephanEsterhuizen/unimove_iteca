<?php
// Shared admin layout (sticky sidebar + Tailwind header).

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role(['admin', 'moderator'], 'index.php');

$is_admin  = current_role() === 'admin';
$base_path = '../';
include __DIR__ . '/../includes/header.php';

/* Nav items + visibility per role */
$nav = [
    ['key' => 'dashboard',    'href' => 'dashboard.php',    'label' => 'Dashboard',     'icon' => 'layout-dashboard', 'roles' => ['admin', 'moderator']],
    ['key' => 'users',        'href' => 'users.php',        'label' => 'Users',         'icon' => 'users',            'roles' => ['admin', 'moderator']],
    ['key' => 'listings',     'href' => 'listings.php',     'label' => 'Listings',      'icon' => 'package',          'roles' => ['admin', 'moderator']],
    ['key' => 'pickup-zones', 'href' => 'pickup-zones.php', 'label' => 'Pickup Zones',  'icon' => 'map-pin',          'roles' => ['admin']],
    ['key' => 'orders',       'href' => 'orders.php',       'label' => 'Orders',        'icon' => 'shopping-bag',     'roles' => ['admin', 'moderator']],
    ['key' => 'reports',      'href' => 'reports.php',      'label' => 'Reports',       'icon' => 'flag',             'roles' => ['admin', 'moderator']],
];
?>

<div class="bg-gray-100 min-h-[calc(100vh-4rem)]">
    <div class="max-w-screen-2xl mx-auto flex">

        <!-- Sidebar -->
        <aside class="hidden md:block w-60 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)] sticky top-16 self-start">
            <div class="px-4 py-4 border-b border-gray-200">
                <div class="text-xs uppercase tracking-wider text-gray-500">Admin Panel</div>
                <div class="text-sm font-semibold flex items-center gap-1">
                    <?= e(current_user()['full_name']) ?>
                    <span class="text-xs <?= $is_admin ? 'text-pink-600' : 'text-blue-600' ?>">
                        (<?= e(current_role()) ?>)
                    </span>
                </div>
            </div>
            <nav class="p-2 space-y-1">
                <?php foreach ($nav as $n):
                    if (!in_array(current_role(), $n['roles'], true)) continue;
                    $is_active = ($active_admin_nav ?? '') === $n['key'];
                    $cls = $is_active
                        ? 'bg-pink-50 text-pink-700 font-semibold'
                        : 'text-gray-700 hover:bg-gray-50';
                ?>
                    <a href="<?= e($n['href']) ?>"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $cls ?>">
                        <i data-lucide="<?= e($n['icon']) ?>" class="icon-sm"></i>
                        <span class="text-sm"><?= e($n['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- Page content -->
        <div class="flex-1 min-w-0 p-4 md:p-8">
