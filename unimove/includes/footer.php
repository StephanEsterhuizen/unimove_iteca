</main>

<?php if (empty($hide_chrome)): ?>
<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="font-semibold mb-4">About</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="#" class="hover:text-pink-600">How it works</a></li>
                    <li><a href="#" class="hover:text-pink-600">Safety tips</a></li>
                    <li><a href="#" class="hover:text-pink-600">Community guidelines</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-4">Categories</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="<?= e($base_path ?? '') ?>browse.php?category=Electronics" class="hover:text-pink-600">Electronics</a></li>
                    <li><a href="<?= e($base_path ?? '') ?>browse.php?category=Textbooks" class="hover:text-pink-600">Textbooks</a></li>
                    <li><a href="<?= e($base_path ?? '') ?>browse.php?category=Furniture" class="hover:text-pink-600">Furniture</a></li>
                    <li><a href="<?= e($base_path ?? '') ?>browse.php?category=Clothing" class="hover:text-pink-600">Clothing</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-4">Support</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="#" class="hover:text-pink-600">Help center</a></li>
                    <li><a href="#" class="hover:text-pink-600">Contact us</a></li>
                    <li><a href="#" class="hover:text-pink-600">Report an issue</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-4">Connect</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="#" class="hover:text-pink-600">Instagram</a></li>
                    <li><a href="#" class="hover:text-pink-600">Twitter</a></li>
                    <li><a href="#" class="hover:text-pink-600">Facebook</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-8 pt-8 border-t border-gray-200 text-center text-sm text-gray-600">
            &copy; <?= date('Y') ?> UniMove Res Essentials. All rights reserved.
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Init Lucide icons -->
<script>
  if (window.lucide) lucide.createIcons();

  // Mobile nav toggle
  document.getElementById('navToggle')?.addEventListener('click', function () {
    document.getElementById('mobileNav')?.classList.toggle('hidden');
  });

  // Profile dropdown toggle
  const pmBtn  = document.getElementById('profileMenuBtn');
  const pmMenu = document.getElementById('profileMenu');
  if (pmBtn && pmMenu) {
    pmBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      pmMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', function (e) {
      if (!pmMenu.contains(e.target) && !pmBtn.contains(e.target)) {
        pmMenu.classList.add('hidden');
      }
    });
  }
</script>
<script src="<?= e($base_path ?? '') ?>assets/js/main.js"></script>
</body>
</html>
