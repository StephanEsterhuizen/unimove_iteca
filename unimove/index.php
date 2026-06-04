<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

/* ---------------- Real stats from DB ---------------- */
$stats = [
    'listings'     => (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
    'students'     => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_verified = 1")->fetchColumn(),
    'transactions' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
];

/* ---------------- Category cards ---------------- */
$category_meta = [
    'Electronics' => ['icon' => 'laptop',  'bg' => 'bg-pink-100',   'fg' => 'text-pink-600'],
    'Textbooks'   => ['icon' => 'book',    'bg' => 'bg-green-100',  'fg' => 'text-green-600'],
    'Furniture'   => ['icon' => 'armchair','bg' => 'bg-purple-100', 'fg' => 'text-purple-600'],
    'Clothing'    => ['icon' => 'shirt',   'bg' => 'bg-pink-100',   'fg' => 'text-pink-600'],
];
$categories = $pdo->query(
    "SELECT name FROM categories WHERE name IN ('Electronics','Textbooks','Furniture','Clothing') ORDER BY name"
)->fetchAll();

/* ---------------- Featured listings ---------------- */
$featured = $pdo->query(
    "SELECT l.listing_id, l.title, l.price, l.condition_type, l.created_at,
            u.full_name AS seller_name,
            pz.name     AS pickup_zone,
            (SELECT image_path FROM listing_images WHERE listing_id = l.listing_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image
       FROM listings l
       JOIN users u           ON u.user_id = l.seller_id
  LEFT JOIN pickup_zones pz   ON pz.zone_id = l.pickup_zone_id
      WHERE l.status = 'active'
   ORDER BY l.created_at DESC
      LIMIT 4"
)->fetchAll();

$page_title = 'Home';
$active_nav = '';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-pink-600 to-pink-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-5xl font-bold mb-6">Buy &amp; Sell on Campus</h1>
            <p class="text-xl mb-8 text-pink-100">
                The trusted marketplace for students. Safe, simple, and local.
            </p>

            <!-- Search Bar -->
            <form action="browse.php" method="GET" class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-2 flex items-center gap-2">
                    <i data-lucide="search" class="icon-md text-gray-400 ml-2"></i>
                    <input type="text" name="q"
                           placeholder="Search for items..."
                           class="flex-1 px-2 py-3 outline-none text-gray-900 bg-transparent">
                    <button type="submit"
                            class="bg-pink-600 text-white px-6 py-3 rounded-md hover:bg-pink-700 transition-colors">
                        Search
                    </button>
                </div>
            </form>

            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-8 mt-12 max-w-3xl mx-auto">
                <div>
                    <div class="text-3xl font-bold"><?= number_format($stats['listings']) ?>+</div>
                    <div class="text-pink-100">Active Listings</div>
                </div>
                <div>
                    <div class="text-3xl font-bold"><?= number_format($stats['students']) ?>+</div>
                    <div class="text-pink-100">Students</div>
                </div>
                <div>
                    <div class="text-3xl font-bold"><?= number_format($stats['transactions']) ?>+</div>
                    <div class="text-pink-100">Transactions</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Shop by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ($categories as $cat):
                $meta = $category_meta[$cat['name']] ?? ['icon' => 'package', 'bg' => 'bg-gray-100', 'fg' => 'text-gray-600'];
            ?>
                <a href="browse.php?category=<?= urlencode($cat['name']) ?>"
                   class="flex flex-col items-center p-8 rounded-xl border-2 border-gray-200 hover:border-pink-600 hover:shadow-lg transition-all">
                    <div class="w-16 h-16 rounded-full <?= $meta['bg'] ?> <?= $meta['fg'] ?> flex items-center justify-center mb-4">
                        <i data-lucide="<?= e($meta['icon']) ?>" class="icon-xl"></i>
                    </div>
                    <span class="font-semibold"><?= e($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Listings -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-12">
            <h2 class="text-3xl font-bold">Featured Listings</h2>
            <a href="browse.php" class="text-pink-600 hover:text-pink-700 font-semibold">View all →</a>
        </div>

        <?php if (empty($featured)): ?>
            <div class="text-center py-12 text-gray-500">
                No listings yet. <a href="create-listing.php" class="text-pink-600 hover:text-pink-700">Be the first to list an item!</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featured as $l): ?>
                    <a href="listing.php?id=<?= (int)$l['listing_id'] ?>"
                       class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-shadow">
                        <img src="<?= e(listing_image_url($l['image'])) ?>"
                             alt="<?= e($l['title']) ?>"
                             class="w-full h-48 object-cover bg-gray-100"
                             onerror="this.src='https://placehold.co/400x300?text=No+image'">
                        <div class="p-4">
                            <h3 class="font-semibold mb-2 truncate"><?= e($l['title']) ?></h3>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-2xl font-bold text-pink-600">R<?= number_format((float)$l['price'], 2) ?></span>
                                <span class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                    <?= e($l['condition_type']) ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-1"><?= e($l['seller_name']) ?></div>
                            <div class="text-xs text-gray-500"><?= e($l['pickup_zone'] ?? '—') ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Features -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center mb-12">Why UniMove?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <div class="text-center">
                <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="shield-check" class="icon-xl text-pink-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Verified Students Only</h3>
                <p class="text-gray-600">
                    All users are verified with student email addresses, ensuring a safe and trusted community.
                </p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="clock" class="icon-xl text-green-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Easy Pickup Scheduling</h3>
                <p class="text-gray-600">
                    Book pickup timeslots at convenient campus locations. No hassle, no waiting around.
                </p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="trending-up" class="icon-xl text-purple-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Local &amp; Sustainable</h3>
                <p class="text-gray-600">
                    Keep items in circulation on campus. Great for your wallet and the environment.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-pink-600 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Ready to get started?</h2>
        <p class="text-xl mb-8 text-pink-100">
            Join thousands of students buying and selling on campus.
        </p>
        <div class="flex gap-4 justify-center flex-wrap">
            <a href="register.php"
               class="bg-white text-pink-600 px-8 py-3 rounded-lg font-semibold hover:bg-pink-50 transition-colors">
                Sign Up Now
            </a>
            <a href="browse.php"
               class="bg-pink-700 text-white px-8 py-3 rounded-lg font-semibold hover:bg-pink-800 transition-colors border-2 border-pink-500">
                Browse Listings
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
