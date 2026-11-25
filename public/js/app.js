/* ==========================================================================
   TaskMate — app.js
   Utilitas inti: dropdown, modal, toast, konfirmasi, copy, badge notifikasi.
   ========================================================================== */

(function () {
    'use strict';

    var metaCsrf = document.querySelector('meta[name="csrf-token"]');
    var CSRF = metaCsrf ? metaCsrf.getAttribute('content') : '';

    window.tmCsrf = CSRF;

    /* ---------- Ikon animasi untuk toast (SVG digambar bertahap lewat CSS) ---------- */
    var TOAST_JUDUL = { sukses: 'Berhasil', gagal: 'Gagal', info: 'Informasi' };

    function toastIcon(type) {
        var ring = '<circle class="ti-ring" cx="26" cy="26" r="23"/>';
        var mark;

        if (type === 'sukses') {
            mark = '<path class="ti-mark" d="M15 27 l7 7 l15 -16"/>';
        } else if (type === 'gagal') {
            mark = '<path class="ti-mark" d="M18 18 L34 34"/><path class="ti-mark ti-mark-2" d="M34 18 L18 34"/>';
        } else {
            mark = '<path class="ti-mark" d="M26 25 v11"/><path class="ti-dot" d="M26 17.5 h.01"/>';
        }

        return '<svg viewBox="0 0 52 52" aria-hidden="true" focusable="false">' + ring + mark + '</svg>';
    }

    /* ---------- Toast (muncul di kanan atas) ---------- */
    var TERAKHIR_TOAST = {};

    window.tmToast = function (pesan, type) {
        type = TOAST_JUDUL[type] ? type : 'info';

        /* Pengaman anti-dobel: pesan & tipe sama dalam 1,5 detik hanya tampil sekali */
        var kunci = type + '|' + (pesan || '');
        var kini = Date.now();
        if (TERAKHIR_TOAST[kunci] && kini - TERAKHIR_TOAST[kunci] < 1500) return;
        TERAKHIR_TOAST[kunci] = kini;

        var durasi = type === 'gagal' ? 7000 : 5000;

        var wrap = document.querySelector('.toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'toast-wrap';
            document.body.appendChild(wrap);
        }

        var el = document.createElement('div');
        el.className = 'toast t-' + type;
        el.setAttribute('role', type === 'gagal' ? 'alert' : 'status');
        el.style.setProperty('--dur', durasi + 'ms');
        el.innerHTML =
            '<span class="toast-icon">' + toastIcon(type) + '</span>' +
            '<div class="toast-text"><strong class="toast-title">' + TOAST_JUDUL[type] + '</strong>' +
            '<span class="toast-msg"></span></div>' +
            '<button type="button" class="toast-close" aria-label="Tutup">✕</button>' +
            '<span class="toast-bar"></span>';
        el.querySelector('.toast-msg').textContent = pesan;
        wrap.appendChild(el);

        function close() {
            el.style.transition = 'opacity .25s, transform .25s';
            el.style.opacity = '0';
            el.style.transform = 'translateX(24px)';
            setTimeout(function () { el.remove(); }, 260);
        }
        el.querySelector('.toast-close').addEventListener('click', close);
        setTimeout(close, durasi);
    };

    /* ---------- Modal ---------- */
    window.tmOpenModal = function (id) {
        var m = document.getElementById(id);
        if (m) {
            m.hidden = false;
            document.body.style.overflow = 'hidden';
        }
    };

    window.tmCloseModal = function (id) {
        var m = document.getElementById(id);
        if (m) {
            m.hidden = true;
            document.body.style.overflow = '';
        }
    };

    document.addEventListener('click', function (e) {
        var openBtn = e.target.closest('[data-modal-open]');
        if (openBtn) {
            e.preventDefault();
            window.tmOpenModal(openBtn.getAttribute('data-modal-open'));
            return;
        }
        var closeBtn = e.target.closest('[data-modal-close]');
        if (closeBtn) {
            var overlay = closeBtn.closest('.modal-overlay');
            if (overlay) {
                overlay.hidden = true;
                document.body.style.overflow = '';
            }
            return;
        }
        // klik pada overlay (di luar modal) menutup modal
        if (e.target.classList && e.target.classList.contains('modal-overlay')) {
            e.target.hidden = true;
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not([hidden])').forEach(function (m) {
                m.hidden = true;
            });
            document.body.style.overflow = '';
        }
    });

    /* ---------- Pratinjau berkas (PDF / gambar) di dalam halaman ----------
       Elemen apa pun dengan atribut data-preview="URL" membuka modal pratinjau
       tanpa pindah halaman atau membuka tab baru. Atribut pendukung:
       data-preview-tipe (pdf|image), data-preview-nama, data-preview-unduh. */
    var pvOverlay = document.getElementById('modalPratinjau');

    if (pvOverlay) {
        var pvTitle = pvOverlay.querySelector('[data-pv-title]');
        var pvBody = pvOverlay.querySelector('[data-pv-body]');
        var pvDownload = pvOverlay.querySelector('[data-pv-download]');

        window.tmPreview = function (opt) {
            pvBody.innerHTML = '';
            pvTitle.textContent = opt.nama || 'Pratinjau berkas';

            if (opt.unduh) {
                pvDownload.href = opt.unduh;
                pvDownload.hidden = false;
            } else {
                pvDownload.hidden = true;
            }

            if (opt.tipe === 'image') {
                var img = document.createElement('img');
                img.src = opt.url;
                img.alt = opt.nama || 'Pratinjau gambar';
                pvBody.appendChild(img);
            } else {
                var frame = document.createElement('iframe');
                frame.src = opt.url + '#view=FitH';
                frame.title = opt.nama || 'Pratinjau PDF';
                pvBody.appendChild(frame);
            }

            window.tmOpenModal('modalPratinjau');
        };

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-preview]');
            if (!trigger) return;
            e.preventDefault();
            window.tmPreview({
                url: trigger.getAttribute('data-preview'),
                tipe: trigger.getAttribute('data-preview-tipe'),
                nama: trigger.getAttribute('data-preview-nama'),
                unduh: trigger.getAttribute('data-preview-unduh')
            });
        });

        // Escape menutup pratinjau saja (modal di bawahnya, mis. detail tugas, tetap terbuka)
        window.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !pvOverlay.hidden) {
                e.stopPropagation();
                pvOverlay.hidden = true;
            }
        }, true);

        // Setiap kali pratinjau ditutup (tombol, klik latar, atau Escape):
        // hentikan pemuatan berkas & pertahankan kunci scroll bila modal lain masih terbuka.
        new MutationObserver(function () {
            if (!pvOverlay.hidden) return;
            pvBody.innerHTML = '';
            document.body.style.overflow = document.querySelector('.modal-overlay:not([hidden])') ? 'hidden' : '';
        }).observe(pvOverlay, { attributes: true, attributeFilter: ['hidden'] });
    }

    /* ---------- Dropdown ---------- */
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-dropdown-toggle]');
        if (toggle) {
            var menu = toggle.parentElement.querySelector('.dropdown-menu');
            var isOpen = menu.classList.contains('open');
            document.querySelectorAll('.dropdown-menu.open').forEach(function (m) { m.classList.remove('open'); });
            if (!isOpen) menu.classList.add('open');
            return;
        }
        if (!e.target.closest('[data-dropdown]')) {
            document.querySelectorAll('.dropdown-menu.open').forEach(function (m) { m.classList.remove('open'); });
        }
    });

    /* ---------- Sidebar mobile ---------- */
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.querySelector('.sidebar-backdrop');

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-sidebar-open]') && sidebar) {
            sidebar.classList.add('open');
            if (backdrop) backdrop.classList.add('show');
        }
        if (e.target.closest('[data-sidebar-close]') && sidebar) {
            sidebar.classList.remove('open');
            if (backdrop) backdrop.classList.remove('show');
        }
    });

    /* ---------- Avatar rusak: otomatis ganti dengan inisial nama ----------
       Foto profil bisa gagal dimuat (storage:link belum dijalankan, URL Google
       kedaluwarsa, atau berkas terhapus). Listener pakai fase capture agar
       menangkap event error dari <img> sebelum browser menampilkan ikon rusak. */
    document.addEventListener('error', function (e) {
        var img = e.target;
        if (!(img instanceof HTMLImageElement) || !img.hasAttribute('data-avatar-fallback')) return;
        var wrap = img.parentElement;
        if (wrap && wrap.classList.contains('avatar')) {
            wrap.classList.add('avatar-fallback');
            wrap.textContent = img.getAttribute('data-avatar-fallback');
        }
        img.remove();
    }, true);

    /* ---------- Sidebar: menu "Workspace Saya" bisa dilipat ---------- */
    var wsGroup = document.getElementById('navWorkspaces');

    if (wsGroup) {
        var WS_KEY = 'tmSidebarWsOpen';

        // Pulihkan posisi lipatan terakhir (default: terbuka)
        try {
            if (localStorage.getItem(WS_KEY) === '0') {
                wsGroup.classList.remove('open');
                var t0 = wsGroup.querySelector('[data-nav-group-toggle]');
                if (t0) t0.setAttribute('aria-expanded', 'false');
            }
        } catch (err) { /* penyimpanan tidak tersedia: abaikan */ }

        document.addEventListener('click', function (e) {
            var toggle = e.target.closest('[data-nav-group-toggle]');
            if (!toggle || !wsGroup.contains(toggle)) return;
            var open = wsGroup.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { localStorage.setItem(WS_KEY, open ? '1' : '0'); } catch (err) { /* abaikan */ }
        });
    }

    /* ---------- Toast dari flash session server ---------- */
    document.querySelectorAll('[data-toast]').forEach(function (el) {
        window.tmToast(el.getAttribute('data-msg'), el.getAttribute('data-toast'));
    });

    /* ---------- Konfirmasi generik untuk form (data-confirm) ---------- */
    window.tmConfirm = function (pesan, onYa) {
        var overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML =
            '<div class="modal modal-sm tm-confirm-box" role="dialog" aria-modal="true">' +
            '  <div class="modal-body">' +
            '    <div class="tm-confirm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:26px;height:26px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg></div>' +
            '    <h3 style="font-size:16px;margin-bottom:8px;">Konfirmasi</h3>' +
            '    <p class="tm-confirm-msg" style="color:var(--muted);font-size:13.5px;line-height:1.6;"></p>' +
            '  </div>' +
            '  <div class="modal-foot">' +
            '    <button type="button" class="btn btn-ghost" data-no>Batal</button>' +
            '    <button type="button" class="btn btn-primary" data-ya>Ya, Lanjutkan</button>' +
            '  </div>' +
            '</div>';
        overlay.querySelector('.tm-confirm-msg').textContent = pesan;
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        overlay.querySelector('[data-no]').addEventListener('click', function () {
            overlay.remove();
            document.body.style.overflow = '';
        });
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                overlay.remove();
                document.body.style.overflow = '';
            }
        });
        overlay.querySelector('[data-ya]').addEventListener('click', function () {
            overlay.remove();
            document.body.style.overflow = '';
            onYa();
        });
    };

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.hasAttribute && form.hasAttribute('data-confirm')) {
            var pesan = form.getAttribute('data-confirm');
            if (form.dataset.confirmed === '1') return;
            e.preventDefault();
            window.tmConfirm(pesan, function () {
                form.dataset.confirmed = '1';
                form.submit();
            });
        }
    }, true);

    /* ---------- Salin ke clipboard ---------- */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy]');
        if (!btn) return;
        var teks = btn.getAttribute('data-copy');
        function sukses() { window.tmToast('Berhasil disalin ke clipboard.', 'sukses'); }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(teks).then(sukses).catch(function () { fallback(); });
        } else {
            fallback();
        }
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = teks;
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); sukses(); } catch (err) { /* diamkan */ }
            ta.remove();
        }
    });

    /* ---------- Toggle tampil/sembunyi kata sandi ---------- */
    document.addEventListener('click', function (e) {
        var eye = e.target.closest('.input-eye');
        if (!eye) return;
        var input = eye.parentElement.querySelector('input');
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        eye.style.color = input.type === 'text' ? 'var(--blue-500)' : '';
    });

    /* ---------- Filter kanban: submit otomatis saat select berubah ---------- */
    document.addEventListener('change', function (e) {
        var el = e.target;
        if (el.matches && el.matches('[data-autosubmit]')) {
            var form = el.closest('form');
            if (form) form.submit();
        }
    });

    /* ---------- Badge notifikasi: pembaruan berkala ---------- */
    var badgeEls = document.querySelectorAll('[data-notif-badge]');
    if (badgeEls.length > 0) {
        setInterval(function () {
            fetch('/notifikasi/badge', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    badgeEls.forEach(function (b) {
                        var c = data.count;
                        if (c > 0) {
                            b.textContent = c > 99 ? '99+' : c;
                            b.style.display = '';
                        } else {
                            b.style.display = 'none';
                        }
                    });
                })
                .catch(function () { /* offline lokal: abaikan */ });
        }, 30000);
    }
})();