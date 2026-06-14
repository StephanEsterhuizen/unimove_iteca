<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Read filters from GET
$q             = trim($_GET['q'] ?? '');
$category      = trim($_GET['category'] ?? 'all');
$condition     = trim($_GET['condition'] ?? 'all');
$pickup        = trim($_GET['pickup_zone'] ?? 'all');
$price_min     = trim($_GET['price_min'] ?? '');
$price_max     = trim($_GET['price_max'] ?? '');
$bundle_only   = isset($_GET['bundle_only']) ? 1 : 0;

$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 12;
$offset        = ($page - 1) * $per_page;

// Build dynamic WHERE
$where  = ["l.status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($category !== 'all' && $category !== '') {
    $where[] = 'c.name = ?';
    $params[] = $category;
}
if ($condition !== 'all' && $condition !== '') {
    $where[] = 'l.condition_type = ?';
    $params[] = $condition;
}
if ($pickup !== 'all' && $pickup !== '') {
    $where[] = 'pz.name = ?';
    $params[] = $pickup;
}
if ($price_min !== '' && is_numeric($price_min)) {
    $where[] = 'l.price >= ?';
    $params[] = (float)$price_min;
}
if ($price_max !== '' && is_numeric($price_max)) {
    $where[] = 'l.price <= ?';
    $params[] = (float)$price_max;
}
if ($bundle_only) {
    $where[] = 'l.is_bundle = 1';
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Total count for pagination
$count_sql = "SELECT COUNT(*) FROM listings l
                JOIN categories c   ON c.category_id = l.category_id
           LEFT JOIN pickup_zones pz ON pz.zone_id = l.pickup_zone_id
                $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

// Fetch listings
$sql = "SELECT l.listing_id, l.title, l.price, l.condition_type, l.is_bundle,
               c.name AS category, pz.name AS pickup_zone,
               u.full_name AS seller_name,
               (SELECT image_path FROM listing_images
                 WHERE listing_id = l.listing_id
              ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image
          FROM listings l
          JOIN categories c   ON c.category_id = l.category_id
          JOIN users u        ON u.user_id = l.seller_id
     LEFT JOIN pickup_zones pz ON pz.zone_id = l.pickup_zone_id
        $where_sql
      ORDER BY l.created_at DESC
         LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$active_filter_count = 0;
foreach (['category' => 'all', 'condition' => 'all', 'pickup_zone' => 'all'] as $k => $def) {
    if (($_GET[$k] ?? $def) !== $def) $active_filter_count++;
}
if ($price_min !== '')   $active_filter_count++;
if ($price_max !== '')   $active_filter_count++;
if ($bundle_only)        $active_filter_count++;

$all_categories   = $pdo->query('SELECT name FROM categories ORDER BY name')->fetchAll();
$all_zones        = $pdo->query('SELECT name FROM pickup_zones WHERE is_active = 1 ORDER BY name')->fetchAll();
$all_conditions   = ['New', 'Like New', 'Good', 'Fair', 'Poor'];

// AJAX MODE: return only the results region
$is_ajax = ($_GET['ajax'] ?? '') === 'results';
if ($is_ajax) {
    header('Content-Type: text/html; charset=utf-8');
    // Render the same "Results" block that lives on the main page.
    ?>
    <div class="mb-4 text-sm text-gray-600">
        <?= $total ?> <?= $total === 1 ? 'item' : 'items' ?> found
    </div>
    <?php if (empty($listings)): ?>
        <div class="text-center py-16">
            <p class="text-gray-600 mb-4">No listings found matching your criteria</p>
            <a href="browse.php" class="text-pink-600 hover:text-pink-700 font-medium" data-clear-filters>Clear all filters</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($listings as $l): ?>
                <a href="listing.php?id=<?= (int)$l['listing_id'] ?>"
                   class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-shadow">
                    <div class="relative">
                        <img src="<?= e(listing_image_url($l['image'])) ?>"
                             alt="<?= e($l['title']) ?>"
                             class="w-full h-48 object-cover bg-gray-100"
                             onerror="this.src='https://placehold.co/400x300?text=No+image'">
                        <?php if ((int)$l['is_bundle'] === 1): ?>
                            <span class="absolute top-2 left-2 bg-pink-600 text-white text-xs px-2 py-1 rounded">Bundle</span>
                        <?php endif; ?>
                    </div>
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
    <?php
    exit;
}

$page_title = 'Browse';
$active_nav = 'browse';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-4">Browse Listings</h1>

        <!-- Search + filter trigger -->
        <form method="GET" action="browse.php" class="flex gap-4 flex-wrap">
            <div class="flex-1 relative min-w-[240px]">
                <i data-lucide="search" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="q" value="<?= e($q) ?>"
                       placeholder="Search for items..."
                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
            <button type="button" id="toggleFilters"
                    class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2 bg-white">
                <i data-lucide="sliders-horizontal" class="icon-md"></i>
                Filters
                <span id="filterBadge" class="<?= $active_filter_count > 0 ? '' : 'hidden' ?> bg-pink-600 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                    <?= $active_filter_count ?>
                </span>
            </button>
            <div id="searchSpinner" class="hidden self-center text-pink-600">
                <i data-lucide="loader-2" class="icon-md animate-spin"></i>
            </div>

            <!-- Sidebar lives inside the form so all filters submit together -->
            <div id="filterSidebar" class="<?= $active_filter_count > 0 ? '' : 'hidden' ?> w-full mt-4">
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Category</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                                <option value="all">All Categories</option>
                                <?php foreach ($all_categories as $c): ?>
                                    <option value="<?= e($c['name']) ?>" <?= $category === $c['name'] ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Condition</label>
                            <select name="condition" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                                <option value="all">All Conditions</option>
                                <?php foreach ($all_conditions as $c): ?>
                                    <option value="<?= e($c) ?>" <?= $condition === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Min price (R)</label>
                            <input type="number" name="price_min" min="0" step="0.01" value="<?= e($price_min) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Max price (R)</label>
                            <input type="number" name="price_max" min="0" step="0.01" value="<?= e($price_max) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Pickup Zone</label>
                            <select name="pickup_zone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                                <option value="all">All Zones</option>
                                <?php foreach ($all_zones as $z): ?>
                                    <option value="<?= e($z['name']) ?>" <?= $pickup === $z['name'] ? 'selected' : '' ?>>
                                        <?= e($z['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between flex-wrap gap-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="bundle_only" value="1" <?= $bundle_only ? 'checked' : '' ?>
                                   class="w-4 h-4 text-pink-600 rounded">
                            Bundles / Starter packs only
                        </label>

                        <?php if ($active_filter_count > 0): ?>
                            <a href="browse.php" class="text-sm text-pink-600 hover:text-pink-700 font-medium">Clear all filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Results (live-replaced by JS on filter changes) -->
    <div id="browseResults">

    <div class="mb-4 text-sm text-gray-600">
        <?= $total ?> <?= $total === 1 ? 'item' : 'items' ?> found
    </div>

    <?php if (empty($listings)): ?>
        <div class="text-center py-16">
            <p class="text-gray-600 mb-4">No listings found matching your criteria</p>
            <a href="browse.php" class="text-pink-600 hover:text-pink-700 font-medium">Clear all filters</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($listings as $l): ?>
                <a href="listing.php?id=<?= (int)$l['listing_id'] ?>"
                   class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-shadow">
                    <div class="relative">
                        <img src="<?= e(listing_image_url($l['image'])) ?>"
                             alt="<?= e($l['title']) ?>"
                             class="w-full h-48 object-cover bg-gray-100"
                             onerror="this.src='https://placehold.co/400x300?text=No+image'">
                        <?php if ((int)$l['is_bundle'] === 1): ?>
                            <span class="absolute top-2 left-2 bg-pink-600 text-white text-xs px-2 py-1 rounded">Bundle</span>
                        <?php endif; ?>
                    </div>
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-8 flex justify-center items-center gap-1">
                <?php
                $base_qs = $_GET;
                unset($base_qs['page']);
                $build = fn($p) => 'browse.php?' . http_build_query(array_merge($base_qs, ['page' => $p]));
                ?>
                <?php if ($page > 1): ?>
                    <a href="<?= e($build($page - 1)) ?>" class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-50">‹ Prev</a>
                <?php endif; ?>
                <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                    <a href="<?= e($build($p)) ?>"
                       class="px-3 py-2 border rounded <?= $p === $page ? 'bg-pink-600 text-white border-pink-600' : 'border-gray-300 hover:bg-gray-50' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="<?= e($build($page + 1)) ?>" class="px-3 py-2 border border-gray-300 rounded hover:bg-gray-50">Next ›</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

    </div><!-- /#browseResults -->
</div>

<script>
(function () {
    const form    = document.querySelector('form[action="browse.php"]');
    const results = document.getElementById('browseResults');
    const spinner = document.getElementById('searchSpinner');
    const badge   = document.getElementById('filterBadge');
    if (!form || !results) return;

    document.getElementById('toggleFilters')?.addEventListener('click', () => {
        document.getElementById('filterSidebar')?.classList.toggle('hidden');
    });

    // Prevent old "Search" submit reload — we always go through fetch now.
    form.addEventListener('submit', (e) => { e.preventDefault(); runSearch(); });

    let timer = null;
    let lastAbort = null;

    function buildQuery() {
        const params = new URLSearchParams();
        new FormData(form).forEach((v, k) => {
            if (v !== '' && v !== 'all') params.append(k, v);
        });
        return params;
    }

    function updateBadge() {
        const fd = new FormData(form);
        let count = 0;
        ['category','condition','pickup_zone'].forEach(k => {
            const v = fd.get(k);
            if (v && v !== 'all' && v !== '') count++;
        });
        ['price_min','price_max'].forEach(k => { if (fd.get(k)) count++; });
        if (fd.get('bundle_only')) count++;
        if (!badge) return;
        if (count > 0) { badge.textContent = count; badge.classList.remove('hidden'); }
        else            { badge.classList.add('hidden'); }
    }

    async function runSearch() {
        const params = buildQuery();
        const ajaxParams = new URLSearchParams(params);
        ajaxParams.set('ajax', 'results');

        // Reflect filters in URL bar (for shareable links + browser back)
        const newUrl = 'browse.php' + (params.toString() ? '?' + params : '');
        history.replaceState(null, '', newUrl);
        updateBadge();

        if (lastAbort) lastAbort.abort();
        lastAbort = new AbortController();

        spinner?.classList.remove('hidden');
        results.style.opacity = '.5';

        try {
            const res = await fetch('browse.php?' + ajaxParams, {
                credentials: 'same-origin',
                signal: lastAbort.signal,
            });
            const html = await res.text();
            results.innerHTML = html;
        } catch (err) {
            if (err.name !== 'AbortError') {
                results.innerHTML = '<div class="text-center py-8 text-red-600">Search failed. Please try again.</div>';
            }
        } finally {
            spinner?.classList.add('hidden');
            results.style.opacity = '';
        }
    }

    // Search field — debounce 250ms
    const searchInput = form.querySelector('input[name="q"]');
    searchInput?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(runSearch, 250);
    });

    // Filter selects + price inputs — fire on change/input
    form.querySelectorAll('select, input[type="number"], input[type="checkbox"]').forEach(el => {
        const evt = el.type === 'number' ? 'input' : 'change';
        el.addEventListener(evt, () => {
            clearTimeout(timer);
            timer = setTimeout(runSearch, el.type === 'number' ? 350 : 0);
        });
    });

    // "Clear all filters" link inside the results region
    document.addEventListener('click', (e) => {
        const link = e.target.closest('[data-clear-filters]');
        if (!link) return;
        e.preventDefault();
        form.reset();
        runSearch();
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
