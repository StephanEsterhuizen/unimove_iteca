<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();
$uid = current_user_id();

/* AJAX thread fetch (used by main.js auto-refresh) */
if (($_GET['ajax'] ?? '') === 'thread') {
    header('Content-Type: text/html; charset=utf-8');
    $with_id = (int)($_GET['with'] ?? 0);
    $listing_id = (int)($_GET['listing'] ?? 0);
    if ($with_id <= 0) exit;
    $stmt = $pdo->prepare(
        'SELECT m.*, u.full_name AS sender_name
           FROM messages m
           JOIN users u ON u.user_id = m.sender_id
          WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
            AND (? = 0 OR m.listing_id = ?)
       ORDER BY m.sent_at ASC'
    );
    $stmt->execute([$uid, $with_id, $with_id, $uid, $listing_id, $listing_id]);
    $thread = $stmt->fetchAll();
    // Mark read
    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?')->execute([$uid, $with_id]);
    foreach ($thread as $m) {
        $mine = (int)$m['sender_id'] === $uid;
        echo '<div class="flex ' . ($mine ? 'justify-end' : 'justify-start') . ' mb-3">'
           . '<div class="max-w-md">'
           . '<div class="px-4 py-2 rounded-lg ' . ($mine ? 'bg-pink-600 text-white' : 'bg-white border border-gray-200') . '">'
           . '<p class="text-sm">' . nl2br(htmlspecialchars($m['message_text'])) . '</p>'
           . '</div>'
           . '<div class="text-xs text-gray-500 mt-1 ' . ($mine ? 'text-right' : 'text-left') . '">'
           . htmlspecialchars(date('M j, g:i A', strtotime($m['sent_at'])))
           . '</div>'
           . '</div></div>';
    }
    exit;
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    require_csrf();
    $to       = (int)($_POST['to'] ?? 0);
    $listing  = (int)($_POST['listing'] ?? 0);
    $text     = trim($_POST['message'] ?? '');
    if ($to > 0 && $text !== '') {
        $stmt = $pdo->prepare(
            'INSERT INTO messages (sender_id, receiver_id, listing_id, message_text)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$uid, $to, $listing ?: null, $text]);
    }
    $qs = http_build_query(['with' => $to, 'listing' => $listing]);
    header('Location: messages.php?' . $qs);
    exit;
}

// Conversation list (distinct partners)
$stmt = $pdo->prepare(
    "SELECT
         CASE WHEN m.sender_id = :uid THEN m.receiver_id ELSE m.sender_id END AS partner_id,
         u.full_name AS partner_name,
         m.listing_id,
         l.title AS listing_title,
         MAX(m.sent_at) AS last_sent,
         SUBSTRING_INDEX(
             GROUP_CONCAT(m.message_text ORDER BY m.sent_at DESC SEPARATOR '||'),
             '||', 1
         ) AS last_message,
         SUM(CASE WHEN m.receiver_id = :uid2 AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread
       FROM messages m
       JOIN users u ON u.user_id = CASE WHEN m.sender_id = :uid3 THEN m.receiver_id ELSE m.sender_id END
  LEFT JOIN listings l ON l.listing_id = m.listing_id
      WHERE m.sender_id = :uid4 OR m.receiver_id = :uid5
   GROUP BY partner_id, partner_name, m.listing_id, l.title
   ORDER BY last_sent DESC"
);
$stmt->execute(['uid' => $uid, 'uid2' => $uid, 'uid3' => $uid, 'uid4' => $uid, 'uid5' => $uid]);
$conversations = $stmt->fetchAll();

// Active conversation
$with_id    = (int)($_GET['with'] ?? 0);
$listing_id = (int)($_GET['listing'] ?? 0);
if (!$with_id && !empty($conversations)) {
    $with_id    = (int)$conversations[0]['partner_id'];
    $listing_id = (int)($conversations[0]['listing_id'] ?? 0);
}

$active_user = null;
$thread      = [];
$listing_meta = null;
if ($with_id > 0) {
    $stmt = $pdo->prepare('SELECT user_id, full_name FROM users WHERE user_id = ?');
    $stmt->execute([$with_id]);
    $active_user = $stmt->fetch();

    if ($active_user) {
        $stmt = $pdo->prepare(
            'SELECT m.*, u.full_name AS sender_name
               FROM messages m
               JOIN users u ON u.user_id = m.sender_id
              WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                AND (? = 0 OR m.listing_id = ?)
           ORDER BY m.sent_at ASC'
        );
        $stmt->execute([$uid, $with_id, $with_id, $uid, $listing_id, $listing_id]);
        $thread = $stmt->fetchAll();

        // Mark as read
        $pdo->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?')->execute([$uid, $with_id]);

        if ($listing_id > 0) {
            $stmt = $pdo->prepare('SELECT title FROM listings WHERE listing_id = ?');
            $stmt->execute([$listing_id]);
            $listing_meta = $stmt->fetch();
        }
    }
}

$page_title = 'Messages';
$active_nav = 'messages';
include __DIR__ . '/includes/header.php';
?>

<div class="h-[calc(100vh-4rem)]">
    <div class="max-w-7xl mx-auto h-full">
        <div class="grid grid-cols-12 h-full border-t border-gray-200">

            <!-- ============== Conversation list ============== -->
            <div class="col-span-12 md:col-span-4 border-r border-gray-200 bg-white overflow-y-auto">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold">Messages</h2>
                </div>

                <?php if (empty($conversations)): ?>
                    <p class="p-6 text-sm text-gray-500">No conversations yet. Start one by messaging a seller from a listing.</p>
                <?php endif; ?>

                <?php foreach ($conversations as $c):
                    $is_active = (int)$c['partner_id'] === $with_id && (int)$c['listing_id'] === $listing_id;
                ?>
                    <a href="messages.php?with=<?= (int)$c['partner_id'] ?>&listing=<?= (int)($c['listing_id'] ?? 0) ?>"
                       class="block w-full p-4 border-b border-gray-200 hover:bg-gray-50 transition-colors text-left <?= $is_active ? 'bg-pink-50' : '' ?>">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 bg-pink-600 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0">
                                <?= e(mb_substr($c['partner_name'], 0, 1)) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold truncate"><?= e($c['partner_name']) ?></span>
                                    <span class="text-xs text-gray-500 flex-shrink-0"><?= e(date('M j', strtotime($c['last_sent']))) ?></span>
                                </div>
                                <?php if ($c['listing_title']): ?>
                                    <div class="text-sm text-gray-600 truncate mb-1"><?= e($c['listing_title']) ?></div>
                                <?php endif; ?>
                                <div class="text-sm <?= $c['unread'] > 0 ? 'font-semibold text-gray-900' : 'text-gray-600' ?> truncate">
                                    <?= e(mb_strimwidth($c['last_message'] ?? '', 0, 60, '…')) ?>
                                </div>
                            </div>
                            <?php if ($c['unread'] > 0): ?>
                                <div class="w-2 h-2 bg-pink-600 rounded-full flex-shrink-0 mt-2"></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- ============== Chat area ============== -->
            <div class="hidden md:flex col-span-12 md:col-span-8 flex-col bg-gray-50">
                <?php if ($active_user): ?>
                    <div class="p-4 bg-white border-b border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-pink-600 rounded-full flex items-center justify-center text-white font-semibold">
                                <?= e(mb_substr($active_user['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-semibold"><?= e($active_user['full_name']) ?></div>
                                <?php if ($listing_meta): ?>
                                    <div class="text-sm text-gray-600">
                                        <a href="listing.php?id=<?= (int)$listing_id ?>" class="hover:text-pink-600">
                                            <?= e($listing_meta['title']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="chatThread"
                         data-conversation-url="messages.php?ajax=thread&with=<?= (int)$with_id ?>&listing=<?= (int)$listing_id ?>"
                         class="flex-1 overflow-y-auto p-4">
                        <?php foreach ($thread as $m):
                            $mine = (int)$m['sender_id'] === $uid;
                        ?>
                            <div class="flex <?= $mine ? 'justify-end' : 'justify-start' ?> mb-3">
                                <div class="max-w-md">
                                    <div class="px-4 py-2 rounded-lg <?= $mine ? 'bg-pink-600 text-white' : 'bg-white border border-gray-200' ?>">
                                        <p class="text-sm"><?= nl2br(e($m['message_text'])) ?></p>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 <?= $mine ? 'text-right' : 'text-left' ?>">
                                        <?= e(date('M j, g:i A', strtotime($m['sent_at']))) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="p-4 bg-white border-t border-gray-200">
                        <form method="POST" action="messages.php" class="flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action"  value="send">
                            <input type="hidden" name="to"      value="<?= (int)$with_id ?>">
                            <input type="hidden" name="listing" value="<?= (int)$listing_id ?>">
                            <input type="text" name="message" required autofocus
                                   placeholder="Type a message..."
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <button type="submit"
                                    class="px-6 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors flex items-center gap-2">
                                <i data-lucide="send" class="icon-sm"></i>
                                Send
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="flex-1 flex items-center justify-center text-gray-500">
                        Select a conversation to start messaging
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
