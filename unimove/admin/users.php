<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin', 'moderator'], 'index.php');

$is_admin = current_role() === 'admin';

/* ---- Actions ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($uid > 0 && $uid !== current_user_id()) {
        switch ($action) {
            case 'verify':
                $pdo->prepare('UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE user_id = ?')
                    ->execute([$uid]);
                $_SESSION['flash_success'] = 'User verified.';
                break;
            case 'suspend':
                $pdo->prepare('UPDATE users SET is_suspended = 1 WHERE user_id = ?')->execute([$uid]);
                $_SESSION['flash_success'] = 'User suspended.';
                break;
            case 'unsuspend':
                $pdo->prepare('UPDATE users SET is_suspended = 0 WHERE user_id = ?')->execute([$uid]);
                $_SESSION['flash_success'] = 'User reinstated.';
                break;
            case 'role':
                if ($is_admin) {
                    $new_role = $_POST['new_role'] ?? '';
                    if (in_array($new_role, ['student', 'moderator', 'admin'], true)) {
                        $pdo->prepare('UPDATE users SET role = ? WHERE user_id = ?')->execute([$new_role, $uid]);
                        $_SESSION['flash_success'] = 'Role updated to ' . $new_role . '.';
                    }
                } else {
                    $_SESSION['flash_error'] = 'Only admins can change user roles.';
                }
                break;
        }
    } else {
        $_SESSION['flash_error'] = 'You cannot modify your own account from here.';
    }
    header('Location: users.php?' . http_build_query($_GET));
    exit;
}

/* ---- Filters ---- */
$q          = trim($_GET['q'] ?? '');
$role_f     = $_GET['role']     ?? 'all';
$verified_f = $_GET['verified'] ?? 'all';

$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(full_name LIKE ? OR email LIKE ? OR university LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if (in_array($role_f, ['student', 'moderator', 'admin'], true)) {
    $where[] = 'role = ?'; $params[] = $role_f;
}
if ($verified_f === 'yes') { $where[] = 'is_verified = 1'; }
if ($verified_f === 'no')  { $where[] = 'is_verified = 0'; }

$sql  = 'SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll();

$page_title       = 'Users';
$active_admin_nav = 'users';
include __DIR__ . '/_layout.php';
?>

<h1 class="text-2xl font-bold mb-6">Users</h1>

<!-- Filters -->
<form method="GET" action="users.php" class="bg-white border border-gray-200 rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs text-gray-500 mb-1">Search</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Name, email, university"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Role</label>
        <select name="role" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            <option value="all"       <?= $role_f === 'all' ? 'selected' : '' ?>>All roles</option>
            <option value="student"   <?= $role_f === 'student' ? 'selected' : '' ?>>Student</option>
            <option value="moderator" <?= $role_f === 'moderator' ? 'selected' : '' ?>>Moderator</option>
            <option value="admin"     <?= $role_f === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Verified</label>
        <select name="verified" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500">
            <option value="all" <?= $verified_f === 'all' ? 'selected' : '' ?>>All</option>
            <option value="yes" <?= $verified_f === 'yes' ? 'selected' : '' ?>>Verified</option>
            <option value="no"  <?= $verified_f === 'no'  ? 'selected' : '' ?>>Unverified</option>
        </select>
    </div>
    <button type="submit" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 text-sm">Apply</button>
    <?php if ($q !== '' || $role_f !== 'all' || $verified_f !== 'all'): ?>
        <a href="users.php" class="text-sm text-pink-600 hover:text-pink-700">Clear</a>
    <?php endif; ?>
</form>

<!-- Table -->
<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">University</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u):
                    $is_self = (int)$u['user_id'] === current_user_id();
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-pink-600 text-white text-sm font-semibold flex items-center justify-center">
                                    <?= e(mb_substr($u['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-medium flex items-center gap-1">
                                        <?= e($u['full_name']) ?>
                                        <?php if ((int)$u['is_verified'] === 1): ?>
                                            <i data-lucide="badge-check" class="icon-xs text-blue-600"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-gray-500"><?= e($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600"><?= e($u['university'] ?? '—') ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs <?=
                                $u['role'] === 'admin'     ? 'bg-pink-100 text-pink-700'
                              : ($u['role'] === 'moderator' ? 'bg-blue-100 text-blue-700'
                                                            : 'bg-gray-100 text-gray-700') ?>">
                                <?= e($u['role']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ((int)$u['is_suspended'] === 1): ?>
                                <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Suspended</span>
                            <?php elseif ((int)$u['is_verified'] === 0): ?>
                                <span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Unverified</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2 justify-end">
                                <?php if ($is_self): ?>
                                    <span class="text-xs text-gray-400 italic">(you)</span>
                                <?php else: ?>

                                    <a href="../profile.php?id=<?= (int)$u['user_id'] ?>" target="_blank"
                                       class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50">View</a>

                                    <?php if ((int)$u['is_verified'] === 0): ?>
                                        <form method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action"  value="verify">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                            <button class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Verify</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ((int)$u['is_suspended'] === 1): ?>
                                        <form method="POST" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action"  value="unsuspend">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                            <button class="text-xs px-2 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Reinstate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Suspend this user?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action"  value="suspend">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                            <button class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">Suspend</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($is_admin): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Change this user\'s role?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action"  value="role">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                            <select name="new_role" class="text-xs px-2 py-1 border border-gray-300 rounded" onchange="this.form.submit()">
                                                <option value="">Change role…</option>
                                                <option value="student"  >Student</option>
                                                <option value="moderator">Moderator</option>
                                                <option value="admin"    >Admin</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No users match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-xs text-gray-500 mt-3">Showing up to 200 users — refine the search to narrow results.</p>

<?php include __DIR__ . '/_layout_end.php'; ?>
