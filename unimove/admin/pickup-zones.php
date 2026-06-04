<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin'], 'index.php'); // admin-only per RBAC matrix

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_zone':
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['location_description'] ?? '');
            if ($name !== '') {
                $pdo->prepare('INSERT INTO pickup_zones (name, location_description, is_active) VALUES (?, ?, 1)')
                    ->execute([$name, $desc]);
                $_SESSION['flash_success'] = 'Pickup zone created.';
            }
            break;

        case 'update_zone':
            $zid  = (int)($_POST['zone_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['location_description'] ?? '');
            $act  = isset($_POST['is_active']) ? 1 : 0;
            if ($zid > 0 && $name !== '') {
                $pdo->prepare('UPDATE pickup_zones SET name = ?, location_description = ?, is_active = ? WHERE zone_id = ?')
                    ->execute([$name, $desc, $act, $zid]);
                $_SESSION['flash_success'] = 'Zone updated.';
            }
            break;

        case 'delete_zone':
            $zid = (int)($_POST['zone_id'] ?? 0);
            if ($zid > 0) {
                $pdo->prepare('DELETE FROM pickup_zones WHERE zone_id = ?')->execute([$zid]);
                $_SESSION['flash_success'] = 'Zone deleted.';
            }
            break;

        case 'add_slot':
            $zid  = (int)($_POST['zone_id'] ?? 0);
            $date = $_POST['slot_date'] ?? '';
            $time = $_POST['slot_time'] ?? '';
            if ($zid > 0 && $date && $time) {
                $pdo->prepare('INSERT INTO timeslots (zone_id, slot_date, slot_time, is_booked) VALUES (?, ?, ?, 0)')
                    ->execute([$zid, $date, $time]);
                $_SESSION['flash_success'] = 'Timeslot added.';
            }
            break;

        case 'delete_slot':
            $sid = (int)($_POST['slot_id'] ?? 0);
            if ($sid > 0) {
                $pdo->prepare('DELETE FROM timeslots WHERE slot_id = ? AND is_booked = 0')->execute([$sid]);
                $_SESSION['flash_success'] = 'Timeslot deleted.';
            }
            break;
    }
    header('Location: pickup-zones.php' . (!empty($_GET['zone']) ? '?zone=' . (int)$_GET['zone'] : ''));
    exit;
}

$zones = $pdo->query('SELECT * FROM pickup_zones ORDER BY name')->fetchAll();

$selected_zone = null;
$slots         = [];
$sel_id = (int)($_GET['zone'] ?? 0);
if ($sel_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM pickup_zones WHERE zone_id = ?');
    $stmt->execute([$sel_id]);
    $selected_zone = $stmt->fetch();
    if ($selected_zone) {
        $stmt = $pdo->prepare('SELECT * FROM timeslots WHERE zone_id = ? ORDER BY slot_date ASC, slot_time ASC');
        $stmt->execute([$sel_id]);
        $slots = $stmt->fetchAll();
    }
}

$page_title       = 'Pickup Zones';
$active_admin_nav = 'pickup-zones';
include __DIR__ . '/_layout.php';
?>

<h1 class="text-2xl font-bold mb-6">Pickup Zones</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Zone list + create form -->
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h2 class="font-semibold mb-3">Add new zone</h2>
            <form method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_zone">
                <input type="text" name="name" placeholder="Zone name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                <textarea name="location_description" placeholder="Location description" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500"></textarea>
                <button class="w-full bg-pink-600 text-white py-2 rounded-lg hover:bg-pink-700 text-sm">Create zone</button>
            </form>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 font-semibold">All zones</div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($zones as $z):
                    $is_sel = $sel_id === (int)$z['zone_id'];
                ?>
                    <a href="?zone=<?= (int)$z['zone_id'] ?>"
                       class="block p-3 hover:bg-gray-50 <?= $is_sel ? 'bg-pink-50' : '' ?>">
                        <div class="flex items-center justify-between">
                            <div class="font-medium"><?= e($z['name']) ?></div>
                            <span class="text-xs <?= (int)$z['is_active'] === 1 ? 'text-green-600' : 'text-gray-400' ?>">
                                <?= (int)$z['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <?php if ($z['location_description']): ?>
                            <div class="text-xs text-gray-500 mt-1 truncate"><?= e($z['location_description']) ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($zones)): ?>
                    <div class="p-4 text-sm text-gray-500">No zones yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Selected zone detail -->
    <div class="lg:col-span-2">
        <?php if ($selected_zone): ?>
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-4">
                <h2 class="font-semibold mb-4">Edit zone: <?= e($selected_zone['name']) ?></h2>
                <form method="POST" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"  value="update_zone">
                    <input type="hidden" name="zone_id" value="<?= (int)$selected_zone['zone_id'] ?>">

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Name</label>
                        <input type="text" name="name" required value="<?= e($selected_zone['name']) ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Description</label>
                        <textarea name="location_description" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500"><?= e($selected_zone['location_description']) ?></textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1"
                               <?= (int)$selected_zone['is_active'] === 1 ? 'checked' : '' ?>
                               class="w-4 h-4 text-pink-600 rounded">
                        Active (students can see this zone)
                    </label>

                    <div class="flex gap-2 pt-2">
                        <button class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 text-sm">Save changes</button>
                    </div>
                </form>

                <form method="POST" class="inline mt-2" onsubmit="return confirm('Delete this zone and all its timeslots?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"  value="delete_zone">
                    <input type="hidden" name="zone_id" value="<?= (int)$selected_zone['zone_id'] ?>">
                    <button class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 text-sm">Delete zone</button>
                </form>
            </div>

            <!-- Timeslots -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold">Timeslots</h2>
                    <span class="text-xs text-gray-500"><?= count($slots) ?> total</span>
                </div>

                <form method="POST" class="flex flex-wrap gap-2 mb-4 items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"  value="add_slot">
                    <input type="hidden" name="zone_id" value="<?= (int)$selected_zone['zone_id'] ?>">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Date</label>
                        <input type="date" name="slot_date" required min="<?= date('Y-m-d') ?>"
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Time</label>
                        <input type="time" name="slot_time" required
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <button class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 text-sm">Add timeslot</button>
                </form>

                <div class="border-t border-gray-100 pt-3">
                    <?php if (empty($slots)): ?>
                        <p class="text-sm text-gray-500">No timeslots yet.</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <?php foreach ($slots as $s): ?>
                                <div class="flex items-center justify-between border border-gray-200 rounded-lg p-3 text-sm">
                                    <div>
                                        <div class="font-medium"><?= e(date('D, M j, Y', strtotime($s['slot_date']))) ?></div>
                                        <div class="text-gray-600 text-xs"><?= e(date('g:i A', strtotime($s['slot_time']))) ?></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs <?= (int)$s['is_booked'] === 1 ? 'text-red-600' : 'text-green-600' ?>">
                                            <?= (int)$s['is_booked'] === 1 ? 'Booked' : 'Available' ?>
                                        </span>
                                        <?php if ((int)$s['is_booked'] === 0): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this timeslot?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action"  value="delete_slot">
                                                <input type="hidden" name="slot_id" value="<?= (int)$s['slot_id'] ?>">
                                                <button class="text-red-500 hover:text-red-700" title="Delete">
                                                    <i data-lucide="x" class="icon-sm"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="bg-white border border-gray-200 rounded-lg p-12 text-center text-gray-500">
                <i data-lucide="map-pin" class="icon-3xl mx-auto mb-3 text-gray-300"></i>
                <p>Select a pickup zone on the left to manage its timeslots.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
