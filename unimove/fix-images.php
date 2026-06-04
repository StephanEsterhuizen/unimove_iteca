<?php
/**
 * fix-images.php — replaces the seeded listing images with hand-picked
 * Unsplash photos that match each listing's title.
 *
 * Idempotent: safe to re-run. Looks up listings by title (the same titles
 * used in seed.php), wipes their existing listing_images rows, and inserts
 * three relevant images each (the first being the cover).
 *
 * Delete this file from production after running it.
 */

require_once __DIR__ . '/includes/db.php';

/**
 * Title => list of three image URLs (cover first).
 * All URLs are stable Unsplash photo IDs sized w=600&h=400&fit=crop.
 */
$map = [
    'MacBook Pro 2022 (M2)' => [
        'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&h=400&fit=crop',
    ],
    'iPhone 13 Pro' => [
        'https://images.unsplash.com/photo-1592286927505-ed0213d4f58c?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1605236453806-6ff36851218e?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1574944985070-8f3ebc6b79d2?w=600&h=400&fit=crop',
    ],
    'Calculus Textbook (12th Ed)' => [
        'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=600&h=400&fit=crop',
    ],
    'Organic Chemistry Notes (Full)' => [
        'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517842645767-c639042777db?w=600&h=400&fit=crop',
    ],
    'IKEA Desk + Chair Set' => [
        'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1554629947-334ff61d85dc?w=600&h=400&fit=crop',
    ],
    'Mini Fridge (50L)' => [
        'https://images.unsplash.com/photo-1571175443880-49e1d25b2bc5?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1584568694244-14fbdf83bd02?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1610557892470-55d9e80c0bce?w=600&h=400&fit=crop',
    ],
    'North Face Winter Jacket' => [
        'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1544022613-e87ca75a784a?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1539533113208-f6df8cc8b543?w=600&h=400&fit=crop',
    ],
    'Nike Running Shoes UK 9' => [
        'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1539185441755-769473a23570?w=600&h=400&fit=crop',
    ],
    'Residence Starter Pack (Bundle)' => [
        'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&h=400&fit=crop',
    ],
    'Storage Bins (Set of 4)' => [
        'https://images.unsplash.com/photo-1581539250439-c96689b516dd?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=600&h=400&fit=crop',
        'https://images.unsplash.com/photo-1556228852-80b6e5eeff06?w=600&h=400&fit=crop',
    ],
];

$updated = [];
$missing = [];

try {
    $pdo->beginTransaction();

    foreach ($map as $title => $urls) {
        $stmt = $pdo->prepare('SELECT listing_id FROM listings WHERE title = ? LIMIT 1');
        $stmt->execute([$title]);
        $lid = (int)$stmt->fetchColumn();
        if ($lid <= 0) {
            $missing[] = $title;
            continue;
        }

        $pdo->prepare('DELETE FROM listing_images WHERE listing_id = ?')->execute([$lid]);

        $insert = $pdo->prepare(
            'INSERT INTO listing_images (listing_id, image_path, is_primary) VALUES (?, ?, ?)'
        );
        foreach ($urls as $i => $url) {
            $insert->execute([$lid, $url, $i === 0 ? 1 : 0]);
        }
        $updated[] = "#$lid — $title (" . count($urls) . " images)";
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $err = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Fix images</title>
<style>
    body { font-family: system-ui, -apple-system, sans-serif; max-width: 760px; margin: 40px auto; padding: 0 20px; color: #1f2330; }
    h1 { color: #C2185B; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:16px 20px; margin: 12px 0; }
    .ok   { color: #15803d; }
    .err  { color: #b91c1c; }
    .warn { color: #b45309; }
    code  { background:#fce7f3; color:#831843; padding:2px 6px; border-radius:4px; }
    a.btn { display:inline-block; background:#C2185B; color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; margin-right:6px; }
    ul li { margin: 2px 0; font-size: 14px; }
</style>
</head>
<body>

<h1>Fix listing images</h1>

<?php if (!empty($err)): ?>
    <div class="card err"><strong>Error:</strong> <?= htmlspecialchars($err) ?></div>
<?php else: ?>
    <div class="card ok"><strong>✓ Images updated for <?= count($updated) ?> listings.</strong></div>

    <div class="card">
        <strong>Updated:</strong>
        <ul>
            <?php foreach ($updated as $u): ?>
                <li><?= htmlspecialchars($u) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if (!empty($missing)): ?>
        <div class="card warn">
            <strong>Skipped (no matching listing in DB):</strong>
            <ul>
                <?php foreach ($missing as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <a class="btn" href="index.php">Home</a>
        <a class="btn" href="browse.php">Browse listings</a>
    </div>
<?php endif; ?>

<div class="card" style="background:#fef3c7; border-color:#fbbf24;">
    <strong>Note:</strong> delete <code>fix-images.php</code> from the server before going live.
</div>
</body>
</html>
