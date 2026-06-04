<?php
require_once __DIR__ . '/includes/auth.php';
http_response_code(404);
$page_title = 'Page not found';
include __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-9xl font-bold text-pink-600 mb-4">404</div>
        <h1 class="text-3xl font-bold mb-4">Page Not Found</h1>
        <p class="text-gray-600 mb-8">
            Sorry, the page you're looking for doesn't exist or has been moved.
        </p>
        <div class="flex gap-4 justify-center flex-wrap">
            <a href="index.php"
               class="px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors flex items-center gap-2">
                <i data-lucide="home" class="icon-md"></i>
                Go Home
            </a>
            <a href="browse.php"
               class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                <i data-lucide="search" class="icon-md"></i>
                Browse Listings
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
