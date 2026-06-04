<?php
require_once __DIR__ . '/auth.php';

$page_title   = $page_title   ?? SITE_NAME;
$active_nav   = $active_nav   ?? '';
$hide_chrome  = $hide_chrome  ?? false; // true on minimal pages (e.g. installer)
$base_path    = $base_path    ?? '';    // pass '../' from /admin/ pages

// Count unread messages for the badge (only when logged in)
$unread_count = 0;
if (is_logged_in()) {
    try {
        require_once __DIR__ . '/db.php';
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
        $stmt->execute([current_user_id()]);
        $unread_count = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $unread_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — <?= e(SITE_NAME) ?></title>

    <!-- Tailwind via CDN with brand overrides -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              pink: {
                50:  '#FCE4EC',
                100: '#FCE4EC',
                200: '#F8BBD0',
                300: '#F48FB1',
                400: '#F06292',
                500: '#EC407A',
                600: '#C2185B',
                700: '#880E4F',
                800: '#560027',
                900: '#3E001C',
              }
            },
            fontFamily: {
              sans: ['Inter', 'system-ui', 'sans-serif'],
            },
          }
        }
      }
    </script>

    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide icons (CDN) -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- Site styles -->
    <link rel="stylesheet" href="<?= e($base_path) ?>assets/css/style.css">
</head>
<body class="min-h-screen flex flex-col bg-gray-50 font-sans antialiased text-gray-900">

<?php if (!$hide_chrome): ?>
<!-- ============================= HEADER ============================= -->
<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <!-- Brand -->
            <a href="<?= e($base_path) ?>index.php" class="flex items-center gap-2">
                <i data-lucide="shopping-bag" class="icon-xl text-pink-600"></i>
                <span class="font-bold text-xl">UniMove Res Essentials</span>
            </a>

            <!-- Mobile toggle -->
            <button id="navToggle" class="md:hidden p-2 -mr-2 text-gray-700" aria-label="Menu">
                <i data-lucide="menu" class="icon-lg"></i>
            </button>

            <!-- Desktop nav -->
            <nav id="mainNav" class="hidden md:flex items-center gap-6">
                <?php if (is_logged_in()): ?>
                    <a href="<?= e($base_path) ?>browse.php"
                       class="hover:text-pink-600 transition-colors <?= $active_nav === 'browse' ? 'text-pink-600 font-semibold' : '' ?>">
                        Browse
                    </a>
                    <a href="<?= e($base_path) ?>create-listing.php"
                       class="flex items-center gap-1 hover:text-pink-600 transition-colors <?= $active_nav === 'sell' ? 'text-pink-600 font-semibold' : '' ?>">
                        <i data-lucide="plus-circle" class="icon-sm"></i>
                        Sell
                    </a>
                    <a href="<?= e($base_path) ?>messages.php"
                       class="relative hover:text-pink-600 transition-colors <?= $active_nav === 'messages' ? 'text-pink-600' : '' ?>"
                       aria-label="Messages">
                        <i data-lucide="message-square" class="icon-md"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] leading-none w-4 h-4 rounded-full flex items-center justify-center">
                                <?= (int)$unread_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= e($base_path) ?>dashboard.php"
                       class="hover:text-pink-600 transition-colors <?= $active_nav === 'dashboard' ? 'text-pink-600' : '' ?>"
                       aria-label="Dashboard">
                        <i data-lucide="layout-dashboard" class="icon-md"></i>
                    </a>

                    <!-- Profile dropdown -->
                    <div class="relative" id="profileMenuWrap">
                        <button id="profileMenuBtn" class="flex items-center gap-2 hover:text-pink-600 transition-colors">
                            <span class="w-8 h-8 rounded-full bg-pink-600 text-white text-sm font-semibold flex items-center justify-center">
                                <?= e(mb_substr(current_user()['full_name'], 0, 1)) ?>
                            </span>
                        </button>
                        <div id="profileMenu"
                             class="hidden absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                            <div class="px-4 py-2 border-b border-gray-200">
                                <div class="font-semibold text-sm"><?= e(current_user()['full_name']) ?></div>
                                <div class="text-xs text-gray-500 truncate"><?= e(current_user()['email']) ?></div>
                            </div>
                            <a href="<?= e($base_path) ?>profile.php?id=<?= current_user_id() ?>"
                               class="block px-4 py-2 text-sm hover:bg-gray-50">My Profile</a>
                            <a href="<?= e($base_path) ?>dashboard.php"
                               class="block px-4 py-2 text-sm hover:bg-gray-50">Dashboard</a>
                            <?php if (in_array(current_role(), ['admin', 'moderator'], true)): ?>
                                <a href="<?= e($base_path) ?>admin/dashboard.php"
                                   class="block px-4 py-2 text-sm hover:bg-gray-50 text-pink-600">Admin Panel</a>
                            <?php endif; ?>
                            <div class="border-t border-gray-200 mt-1 pt-1">
                                <a href="<?= e($base_path) ?>logout.php"
                                   class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Log out</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= e($base_path) ?>browse.php" class="hover:text-pink-600 transition-colors">Browse</a>
                    <a href="<?= e($base_path) ?>login.php" class="hover:text-pink-600 transition-colors">Login</a>
                    <a href="<?= e($base_path) ?>register.php"
                       class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition-colors">
                        Sign Up
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Mobile nav drawer -->
        <nav id="mobileNav" class="md:hidden hidden pb-4 border-t border-gray-100 pt-3 space-y-2">
            <?php if (is_logged_in()): ?>
                <a href="<?= e($base_path) ?>browse.php" class="block px-3 py-2 rounded hover:bg-gray-50">Browse</a>
                <a href="<?= e($base_path) ?>create-listing.php" class="block px-3 py-2 rounded hover:bg-gray-50">Sell</a>
                <a href="<?= e($base_path) ?>messages.php" class="block px-3 py-2 rounded hover:bg-gray-50">
                    Messages<?= $unread_count > 0 ? ' <span class="ml-2 inline-block bg-red-500 text-white text-xs px-2 rounded-full">'.(int)$unread_count.'</span>' : '' ?>
                </a>
                <a href="<?= e($base_path) ?>dashboard.php" class="block px-3 py-2 rounded hover:bg-gray-50">Dashboard</a>
                <a href="<?= e($base_path) ?>profile.php?id=<?= current_user_id() ?>" class="block px-3 py-2 rounded hover:bg-gray-50">Profile</a>
                <?php if (in_array(current_role(), ['admin', 'moderator'], true)): ?>
                    <a href="<?= e($base_path) ?>admin/dashboard.php" class="block px-3 py-2 rounded text-pink-600 hover:bg-pink-50">Admin Panel</a>
                <?php endif; ?>
                <a href="<?= e($base_path) ?>logout.php" class="block px-3 py-2 rounded text-red-600 hover:bg-red-50">Log out</a>
            <?php else: ?>
                <a href="<?= e($base_path) ?>browse.php" class="block px-3 py-2 rounded hover:bg-gray-50">Browse</a>
                <a href="<?= e($base_path) ?>login.php" class="block px-3 py-2 rounded hover:bg-gray-50">Login</a>
                <a href="<?= e($base_path) ?>register.php" class="block px-3 py-2 rounded bg-pink-600 text-white hover:bg-pink-700">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php endif; ?>

<!-- ============================ FLASH MSGS ========================== -->
<?php
$flash_error   = flash_pop('flash_error');
$flash_success = flash_pop('flash_success');
?>
<?php if ($flash_error || $flash_success): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <?php if ($flash_error): ?>
            <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-3">
                <i data-lucide="alert-circle" class="icon-md flex-shrink-0 mt-0.5"></i>
                <div class="flex-1"><?= e($flash_error) ?></div>
            </div>
        <?php endif; ?>
        <?php if ($flash_success): ?>
            <div class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 mb-3">
                <i data-lucide="check-circle" class="icon-md flex-shrink-0 mt-0.5"></i>
                <div class="flex-1"><?= e($flash_success) ?></div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ============================ MAIN ================================ -->
<main class="flex-1">
