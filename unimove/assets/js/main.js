/* =====================================================================
   UniMove Res Essentials — Global JS
   Reusable behaviours used across pages.
   ===================================================================== */

(function () {
    'use strict';

    /* ---------- OTP input: auto-advance + paste support ---------- */
    document.querySelectorAll('.um-otp-boxes').forEach(function (group) {
        const cells     = Array.from(group.querySelectorAll('.um-otp-cell'));
        const targetSel = group.getAttribute('data-otp-target');
        const hidden    = targetSel ? document.querySelector(targetSel) : null;

        function syncHidden() {
            if (hidden) hidden.value = cells.map(c => c.value).join('');
        }

        cells.forEach(function (cell, idx) {
            cell.addEventListener('input', function () {
                cell.value = cell.value.replace(/\D/g, '').slice(0, 1);
                if (cell.value && idx < cells.length - 1) cells[idx + 1].focus();
                syncHidden();
            });
            cell.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !cell.value && idx > 0) cells[idx - 1].focus();
                if (e.key === 'ArrowLeft'  && idx > 0)                cells[idx - 1].focus();
                if (e.key === 'ArrowRight' && idx < cells.length - 1) cells[idx + 1].focus();
            });
            cell.addEventListener('paste', function (e) {
                const text   = (e.clipboardData || window.clipboardData).getData('text');
                const digits = text.replace(/\D/g, '').slice(0, cells.length);
                if (!digits) return;
                e.preventDefault();
                digits.split('').forEach((d, i) => { if (cells[i]) cells[i].value = d; });
                cells[Math.min(digits.length, cells.length - 1)].focus();
                syncHidden();
            });
        });
    });

    /* ---------- Image preview for create-listing ---------- */
    const imageInput   = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    if (imageInput && imagePreview) {
        const MAX = 5;
        let files = [];

        function render() {
            imagePreview.innerHTML = '';
            files.forEach((file, idx) => {
                const url = URL.createObjectURL(file);
                const cell = document.createElement('div');
                cell.className = 'relative aspect-square';
                cell.innerHTML = `
                    <img src="${url}" class="w-full h-full object-cover rounded-lg" alt="">
                    <button type="button" data-remove="${idx}"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 leading-none">
                        <i data-lucide="x" class="icon-sm"></i>
                    </button>
                    ${idx === 0 ? '<div class="absolute bottom-1 left-1 bg-pink-600 text-white text-xs px-2 py-1 rounded">Cover</div>' : ''}
                `;
                imagePreview.appendChild(cell);
            });
            if (files.length < MAX) {
                const lbl = document.createElement('label');
                lbl.className = 'aspect-square border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-pink-500 hover:bg-pink-50 transition-colors';
                lbl.innerHTML = `
                    <i data-lucide="upload" class="icon-lg text-gray-400 mb-1"></i>
                    <span class="text-xs text-gray-500">Upload</span>
                `;
                lbl.appendChild(imageInput); // re-mount the file input inside the label
                imagePreview.appendChild(lbl);
            }
            if (window.lucide) lucide.createIcons();
            syncToInput();
        }

        function syncToInput() {
            // Repackage selected files into DataTransfer so server receives them
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f));
            imageInput.files = dt.files;
            // Submit button state
            const submitBtn = document.getElementById('listingSubmitBtn');
            if (submitBtn) submitBtn.disabled = files.length < 3;
            const counter = document.getElementById('imageCount');
            if (counter) counter.textContent = files.length;
        }

        imageInput.addEventListener('change', function () {
            const added = Array.from(this.files || []);
            files = files.concat(added).slice(0, MAX);
            render();
        });

        imagePreview.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-remove]');
            if (!btn) return;
            files.splice(parseInt(btn.dataset.remove, 10), 1);
            render();
        });

        render();
    }

    /* ---------- Messages auto-refresh (every 5s) ---------- */
    const chatThread = document.getElementById('chatThread');
    if (chatThread && chatThread.dataset.conversationUrl) {
        const url = chatThread.dataset.conversationUrl;
        setInterval(function () {
            fetch(url, { credentials: 'same-origin' })
                .then(r => r.text())
                .then(html => {
                    chatThread.innerHTML = html;
                    chatThread.scrollTop = chatThread.scrollHeight;
                })
                .catch(() => {});
        }, 5000);
        // initial scroll to bottom
        chatThread.scrollTop = chatThread.scrollHeight;
    }

    /* ---------- Listing gallery: click thumbnail to swap main image ---------- */
    document.querySelectorAll('[data-gallery-main]').forEach(function (main) {
        const galleryId = main.dataset.galleryMain;
        document.querySelectorAll(`[data-gallery-thumb="${galleryId}"]`).forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                main.src = thumb.dataset.src;
                document.querySelectorAll(`[data-gallery-thumb="${galleryId}"]`)
                    .forEach(t => t.classList.remove('border-pink-600'));
                thumb.classList.add('border-pink-600');
            });
        });
    });

})();
