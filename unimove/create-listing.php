<?php
// Create or edit a listing. ?id=<n> + ownership = edit mode.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

// Catch a POST that exceeded post_max_size (empty $_POST and $_FILES).
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && empty($_POST)
    && empty($_FILES)
    && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $max_mb = (int)ini_get('post_max_size');
    $_SESSION['flash_error'] = "Your upload exceeded the server's limit ({$max_mb} MB total). Please choose fewer or smaller images and try again.";
    header('Location: create-listing.php' . ($edit_id ? '?id=' . (int)$edit_id : ''));
    exit;
}

$edit_id  = (int)($_GET['id'] ?? 0);
$is_edit  = $edit_id > 0;
$listing  = null;
$errors   = [];
$success  = false;

// Load existing listing for editing
if ($is_edit) {
    $stmt = $pdo->prepare('SELECT * FROM listings WHERE listing_id = ? AND seller_id = ?');
    $stmt->execute([$edit_id, current_user_id()]);
    $listing = $stmt->fetch();
    if (!$listing) { http_response_code(403); die('You do not have permission to edit this listing.'); }
}

// Lookups
$categories = $pdo->query('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();
$zones      = $pdo->query('SELECT zone_id, name FROM pickup_zones WHERE is_active = 1 ORDER BY name')->fetchAll();
$conditions = ['New', 'Like New', 'Good', 'Fair', 'Poor'];

$existing_images = [];
if ($is_edit) {
    $stmt = $pdo->prepare('SELECT image_id, image_path FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, image_id ASC');
    $stmt->execute([$edit_id]);
    $existing_images = $stmt->fetchAll();
}

// Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $condition   = trim($_POST['condition_type'] ?? '');
    $zone_id     = (int)($_POST['pickup_zone_id'] ?? 0);
    $is_bundle   = isset($_POST['is_bundle']) ? 1 : 0;
    $timeslots   = $_POST['timeslot'] ?? []; // array of ['date'=>..,'time'=>..]

    if (mb_strlen($title) < 3)              $errors[] = 'Title must be at least 3 characters.';
    if (mb_strlen($description) < 50)       $errors[] = 'Description must be at least 50 characters.';
    if ($price <= 0)                        $errors[] = 'Price must be greater than zero.';
    if ($category_id <= 0)                  $errors[] = 'Please select a category.';
    if (!in_array($condition, $conditions, true)) $errors[] = 'Please select a condition.';
    if ($zone_id <= 0)                      $errors[] = 'Please select a pickup zone.';

    // Validate uploads (only on create or when adding new on edit)
    $uploaded     = $_FILES['images'] ?? null;
    $upload_count = $uploaded && !empty($uploaded['name'][0]) ? count(array_filter($uploaded['name'])) : 0;

    if (!$is_edit && $upload_count < 3) {
        $errors[] = 'Please upload at least 3 photos.';
    }
    if ($upload_count > 5) {
        $errors[] = 'You can upload a maximum of 5 photos.';
    }

    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
    $stored_files = []; // [filename, is_primary]

    if (empty($errors) && $upload_count > 0) {
        if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);

        for ($i = 0; $i < $upload_count; $i++) {
            if (($uploaded['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload failed for image #' . ($i + 1) . '.';
                continue;
            }
            $tmp  = $uploaded['tmp_name'][$i];
            $mime = mime_content_type($tmp);
            if (!in_array($mime, $allowed_mime, true)) {
                $errors[] = 'Image #' . ($i + 1) . ' is not JPG, PNG, or WEBP.';
                continue;
            }
            $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
            $name = bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($tmp, UPLOAD_DIR . $name)) {
                $errors[] = 'Could not save image #' . ($i + 1) . '.';
                continue;
            }
            $stored_files[] = [
                'name'       => $name,
                'is_primary' => $i === 0 && !$is_edit ? 1 : 0,
            ];
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();

        if ($is_edit) {
            $stmt = $pdo->prepare(
                'UPDATE listings
                    SET title = ?, description = ?, price = ?, category_id = ?,
                        condition_type = ?, pickup_zone_id = ?, is_bundle = ?
                  WHERE listing_id = ? AND seller_id = ?'
            );
            $stmt->execute([$title, $description, $price, $category_id, $condition, $zone_id, $is_bundle, $edit_id, current_user_id()]);
            $listing_id = $edit_id;
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO listings (seller_id, title, description, price, category_id, condition_type,
                                       pickup_zone_id, is_bundle, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")'
            );
            $stmt->execute([current_user_id(), $title, $description, $price, $category_id, $condition, $zone_id, $is_bundle]);
            $listing_id = (int)$pdo->lastInsertId();
        }

        foreach ($stored_files as $img) {
            $stmt = $pdo->prepare('INSERT INTO listing_images (listing_id, image_path, is_primary) VALUES (?, ?, ?)');
            $stmt->execute([$listing_id, $img['name'], $img['is_primary']]);
        }

        // Timeslots — create new ones in this zone for the seller's chosen times
        foreach ($timeslots as $t) {
            $date = trim($t['date'] ?? '');
            $time = trim($t['time'] ?? '');
            if ($date === '' || $time === '') continue;
            try {
                $stmt = $pdo->prepare('INSERT INTO timeslots (zone_id, slot_date, slot_time, is_booked) VALUES (?, ?, ?, 0)');
                $stmt->execute([$zone_id, $date, $time]);
            } catch (PDOException $e) { /* ignore dups */ }
        }

        $pdo->commit();
        $_SESSION['flash_success'] = $is_edit
            ? 'Listing updated.'
            : 'Listing submitted! It will appear after admin approval.';
        header('Location: dashboard.php');
        exit;
    }
}

$page_title = $is_edit ? 'Edit listing' : 'Create listing';
$active_nav = 'sell';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-8"><?= $is_edit ? 'Edit Listing' : 'Create New Listing' ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="<?= $is_edit ? 'create-listing.php?id=' . (int)$edit_id : 'create-listing.php' ?>"
          class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
        <?= csrf_field() ?>

        <!-- Photos -->
        <div>
            <label class="block text-sm font-medium mb-2">
                Photos <span class="text-red-500">*</span>
                <span class="text-xs text-gray-500 ml-2">(At least 3 photos required — count: <span id="imageCount">0</span>/5)</span>
            </label>

            <?php if ($is_edit && !empty($existing_images)): ?>
                <div class="grid grid-cols-5 gap-4 mb-4">
                    <?php foreach ($existing_images as $img): ?>
                        <div class="aspect-square">
                            <img src="<?= e(listing_image_url($img['image_path'])) ?>" class="w-full h-full object-cover rounded-lg" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-500 mb-3">Existing photos shown above. Upload additional photos to add to this listing.</p>
            <?php endif; ?>

            <div id="imagePreview" class="grid grid-cols-5 gap-4">
                <label class="aspect-square border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-pink-500 hover:bg-pink-50 transition-colors">
                    <i data-lucide="upload" class="icon-lg text-gray-400 mb-1"></i>
                    <span class="text-xs text-gray-500">Upload</span>
                    <input id="imageInput" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="hidden">
                </label>
            </div>
            <p class="text-xs text-gray-500 mt-2">JPG, PNG, or WEBP. Max 5 photos. The first photo will be the cover image.</p>
        </div>

        <!-- Title -->
        <div>
            <label class="block text-sm font-medium mb-2">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" required minlength="3" maxlength="200"
                   value="<?= e($listing['title'] ?? '') ?>"
                   placeholder="e.g., MacBook Pro 2022"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium mb-2">Description <span class="text-red-500">*</span><span class="text-xs text-gray-500 ml-2">(min. 50 characters)</span></label>
            <textarea name="description" rows="5" required minlength="50"
                      placeholder="Describe your item in detail..."
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500"><?= e($listing['description'] ?? '') ?></textarea>
        </div>

        <!-- Price -->
        <div>
            <label class="block text-sm font-medium mb-2">Price (R) <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600">R</span>
                <input type="number" name="price" required min="0.01" step="0.01"
                       value="<?= e($listing['price'] ?? '') ?>"
                       placeholder="0.00"
                       class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
            </div>
        </div>

        <!-- Category & Condition -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-2">Category <span class="text-red-500">*</span></label>
                <select name="category_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>" <?= (int)($listing['category_id'] ?? 0) === (int)$c['category_id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Condition <span class="text-red-500">*</span></label>
                <select name="condition_type" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <option value="">Select condition</option>
                    <?php foreach ($conditions as $c): ?>
                        <option value="<?= e($c) ?>" <?= ($listing['condition_type'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Pickup Zone -->
        <div>
            <label class="block text-sm font-medium mb-2">Pickup Zone <span class="text-red-500">*</span></label>
            <select name="pickup_zone_id" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                <option value="">Select pickup zone</option>
                <?php foreach ($zones as $z): ?>
                    <option value="<?= (int)$z['zone_id'] ?>" <?= (int)($listing['pickup_zone_id'] ?? 0) === (int)$z['zone_id'] ? 'selected' : '' ?>>
                        <?= e($z['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Bundle toggle -->
        <div>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_bundle" value="1" <?= !empty($listing['is_bundle']) ? 'checked' : '' ?>
                       class="w-4 h-4 text-pink-600 rounded">
                <span class="text-sm">This is a bundle / starter pack (multiple items together)</span>
            </label>
        </div>

        <!-- Timeslots -->
        <div>
            <label class="block text-sm font-medium mb-2">Available Pickup Timeslots</label>
            <div id="timeslots" class="space-y-3">
                <div class="flex gap-3 timeslot-row">
                    <input type="date" name="timeslot[0][date]" min="<?= date('Y-m-d') ?>"
                           class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    <input type="time" name="timeslot[0][time]"
                           class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>
            </div>
            <button type="button" id="addTimeslot"
                    class="mt-3 flex items-center gap-2 text-pink-600 hover:text-pink-700 text-sm font-medium">
                <i data-lucide="plus" class="icon-sm"></i>
                Add another timeslot
            </button>
        </div>

        <!-- Submit -->
        <div class="flex gap-4 pt-4">
            <a href="dashboard.php" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-center">Cancel</a>
            <button type="submit" id="listingSubmitBtn" <?= $is_edit ? '' : 'disabled' ?>
                    class="flex-1 px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                <?= $is_edit ? 'Update Listing' : 'Create Listing' ?>
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    // Add timeslot rows
    let tsIndex = 1;
    document.getElementById('addTimeslot')?.addEventListener('click', () => {
        const container = document.getElementById('timeslots');
        const row = document.createElement('div');
        row.className = 'flex gap-3 timeslot-row';
        row.innerHTML = `
            <input type="date" name="timeslot[${tsIndex}][date]" min="<?= date('Y-m-d') ?>"
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
            <input type="time" name="timeslot[${tsIndex}][time]"
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
            <button type="button" class="px-3 text-red-500 hover:text-red-700" data-remove-ts>
                <i data-lucide="x" class="icon-md"></i>
            </button>
        `;
        container.appendChild(row);
        tsIndex++;
        if (window.lucide) lucide.createIcons();
    });
    document.getElementById('timeslots')?.addEventListener('click', (e) => {
        const rmv = e.target.closest('[data-remove-ts]');
        if (rmv) rmv.closest('.timeslot-row').remove();
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
