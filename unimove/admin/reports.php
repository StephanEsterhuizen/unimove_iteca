<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin', 'moderator'], 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $rid    = (int)($_POST['report_id'] ?? 0);
    $valid  = ['mark_reviewed' => 'reviewed', 'resolve' => 'resolved', 'reopen' => 'open'];
    if ($rid > 0 && isset($valid[$action])) {
        $pdo->prepare('UPDATE reports SET status = ? WHERE report_id = ?')->execute([$valid[$action], $rid]);
        $_SESSION['flash_success'] = 'Report marked ' . $valid[$action] . '.';
    }
    header('Location: reports.php?' . http_build_query($_GET));
    exit;
}

$status_f = $_GET['status'] ?? 'open';

$where  = ['1=1'];
$params = [];
if (in_array($status_f, ['open', 'reviewed', 'resolved'], true)) {
    $where[] = 'r.status = ?'; $params[] = $status_f;
}

$sql = "SELECT r.*,
               u.full_name AS reporter_name,
               l.title     AS listing_title,
               ru.full_name AS reported_user_name
          FROM reports r
          JOIN users u        ON u.user_id  = r.reporter_id
     LEFT JOIN listings l     ON l.listing_id = r.listing_id
     LEFT JOIN users ru       ON ru.user_id = r.user_id
         WHERE " . implode(' AND ', $where) . "
      ORDER BY r.created_at DESC
         LIMIT 200";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$reports = $stmt->fetchAll();

$counts = $pdo->query(
    "SELECT
       SUM(CASE WHEN status = 'open'     THEN 1 ELSE 0 END) AS open_,
       SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed_,
       SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_
     FROM reports"
)->fetch();

$page_title       = 'Reports';
$active_admin_nav = 'reports';
include __DIR__ . '/_layout.php';
?>

<h1 class="text-2xl font-bold mb-6">Reports</h1>

<div class="flex gap-2 mb-6 flex-wrap">
    <?php
    $tabs = [
        'open'     => ['Open',     (int)$counts['open_'],     'border-red-500 text-red-700'],
        'reviewed' => ['Reviewed', (int)$counts['reviewed_'], 'border-yellow-500 text-yellow-700'],
        'resolved' => ['Resolved', (int)$counts['resolved_'], 'border-gray-300 text-gray-700'],
    ];
    foreach ($tabs as $k => [$lbl, $cnt, $col]):
        $is_active = $status_f === $k;
    ?>
        <a href="?status=<?= e($k) ?>"
           class="px-3 py-1.5 rounded-full text-sm border <?= $is_active ? 'bg-pink-600 text-white border-pink-600' : "bg-white $col" ?>">
            <?= e($lbl) ?> · <?= $cnt ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($reports)): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-12 text-center text-gray-500">
        <i data-lucide="flag" class="icon-3xl mx-auto mb-3 text-gray-300"></i>
        <p>No reports in this view.</p>
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($reports as $r):
            $type = $r['listing_id'] ? 'Listing' : ($r['user_id'] ? 'User' : 'Other');
        ?>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700"><?= e($type) ?></span>
                            <span class="text-xs px-2 py-0.5 rounded <?=
                                $r['status'] === 'open'     ? 'bg-red-100 text-red-700'
                              : ($r['status'] === 'reviewed' ? 'bg-yellow-100 text-yellow-800'
                                                             : 'bg-gray-100 text-gray-700') ?>">
                                <?= e($r['status']) ?>
                            </span>
                            <span class="text-xs text-gray-500"><?= e(date('M j, Y g:i A', strtotime($r['created_at']))) ?></span>
                        </div>
                        <div class="mt-2 font-medium">
                            <?php if ($r['listing_id']): ?>
                                <a href="../listing.php?id=<?= (int)$r['listing_id'] ?>" target="_blank" class="hover:text-pink-600">
                                    Listing: <?= e($r['listing_title']) ?>
                                </a>
                            <?php elseif ($r['user_id']): ?>
                                <a href="../profile.php?id=<?= (int)$r['user_id'] ?>" target="_blank" class="hover:text-pink-600">
                                    User: <?= e($r['reported_user_name']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">Reported by <?= e($r['reporter_name']) ?></div>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($r['status'] === 'open'): ?>
                            <form method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action"    value="mark_reviewed">
                                <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                                <button class="text-xs px-3 py-1.5 bg-yellow-100 text-yellow-800 rounded hover:bg-yellow-200">Mark reviewed</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($r['status'] !== 'resolved'): ?>
                            <form method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action"    value="resolve">
                                <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                                <button class="text-xs px-3 py-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200">Resolve</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action"    value="reopen">
                                <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                                <button class="text-xs px-3 py-1.5 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Reopen</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bg-gray-50 rounded p-3 text-sm text-gray-800 whitespace-pre-line"><?= e($r['reason']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/_layout_end.php'; ?>
