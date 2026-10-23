/* ==========================================================================
   TaskMate — kanban.js
   Fitur khusus: kolom status TERSEMBUNYI secara default dan baru muncul
   saat chip status diklik (permintaan pengguna). Termasuk drag & drop kartu.
   ========================================================================== */

(function () {
    'use strict';

    var board = document.getElementById('kanbanBoard');
    if (!board) return;

    var wsId = board.getAttribute('data-workspace');
    var storageKey = 'tm_kanban_open_' + wsId;

    var chips = document.querySelectorAll('[data-kolom].k-chip');
    var cols = board.querySelectorAll('.kanban-col[data-kolom]');

    /* ---------- Simpan / muat kolom yang terbuka ---------- */
    function getOpen() {
        try { return JSON.parse(sessionStorage.getItem(storageKey) || '[]'); }
        catch (e) { return []; }
    }

    function setOpen(arr) {
        sessionStorage.setItem(storageKey, JSON.stringify(arr));
    }

    function applyOpen() {
        var open = getOpen();
        cols.forEach(function (col) {
            var key = col.getAttribute('data-kolom');
            var show = open.indexOf(key) !== -1;
            col.hidden = !show;
        });
        chips.forEach(function (chip) {
            var key = chip.getAttribute('data-kolom');
            chip.classList.toggle('open', open.indexOf(key) !== -1);
        });
        var semua = document.getElementById('toggleSemua');
        if (semua) {
            semua.innerHTML = open.length === 4
                ? 'Sembunyikan Semua'
                : 'Tampilkan Semua';
        }
    }

    function toggleKolom(key) {
        var open = getOpen();
        var idx = open.indexOf(key);
        if (idx === -1) open.push(key); else open.splice(idx, 1);
        setOpen(open);
        applyOpen();
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            toggleKolom(chip.getAttribute('data-kolom'));
        });
    });

    // Tombol silang pada kolom: sembunyikan kolom tsb
    document.querySelectorAll('[data-kolom-tutup]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            toggleKolom(btn.getAttribute('data-kolom-tutup'));
        });
    });

    // Tampilkan/sembunyikan semua sekaligus
    var toggleSemua = document.getElementById('toggleSemua');
    if (toggleSemua) {
        toggleSemua.addEventListener('click', function () {
            setOpen(getOpen().length === 4 ? [] : ['todo', 'in_progress', 'review', 'done']);
            applyOpen();
        });
    }

    applyOpen();

    /* ---------- Pencarian cepat di sisi klien ---------- */
    var searchInput = document.querySelector('#filterForm input[name="q"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('.task-card').forEach(function (card) {
                var judul = card.getAttribute('data-judul') || '';
                card.style.display = (!q || judul.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    /* ---------- Pindah status via AJAX ---------- */
    function pindahStatus(taskId, newStatus, catatan, cb) {
        var body = new URLSearchParams();
        body.append('status', newStatus);
        if (catatan) body.append('catatan', catatan);

        fetch('/workspace/' + wsId + '/tugas/' + taskId + '/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': window.tmCsrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body.toString()
        })
            .then(function (r) {
                return r.json().catch(function () { return { message: 'Terjadi kesalahan server.' }; })
                    .then(function (data) { return { ok: r.ok, data: data }; });
            })
            .then(function (res) {
                if (res.ok) {
                    window.tmToast(res.data.pesan || 'Status diperbarui.', 'sukses');
                    if (cb) cb(true);
                } else {
                    window.tmToast(res.data.message || 'Tidak dapat memindahkan tugas.', 'gagal');
                    if (cb) cb(false);
                }
            })
            .catch(function () {
                window.tmToast('Koneksi bermasalah. Coba lagi.', 'gagal');
                if (cb) cb(false);
            });
    }

    // Tombol maju/mundur pada kartu (tanpa reload: pindahkan node antar kolom)
    function pindahkanKartu(taskId, newStatus) {
        pindahStatus(taskId, newStatus, null, function (ok) {
            if (!ok) return;
            var card = document.querySelector('.task-card[data-task="' + taskId + '"]');
            var col = board.querySelector('.kanban-col[data-kolom="' + newStatus + '"] .kc-body');
            if (card && col) {
                var dari = card.closest('.kc-body');
                card.remove();
                var hint = col.querySelector('.kc-empty');
                if (hint) hint.remove();
                col.insertBefore(card, col.querySelector('.kc-drop-hint'));
                // perbarui jumlah kartu pada header kolom
                board.querySelectorAll('.kanban-col').forEach(function (c) {
                    var n = c.querySelectorAll('.task-card').length;
                    var cnt = c.querySelector('.kc-count');
                    if (cnt) cnt.textContent = n;
                });
                // kartu pindah kolom → muat ulang agar tombol izin & warna deadline segar
                setTimeout(function () { window.location.reload(); }, 650);
            } else {
                window.location.reload();
            }
        });
    }

    document.addEventListener('click', function (e) {
        var moveBtn = e.target.closest('[data-move]');
        if (moveBtn && !moveBtn.classList.contains('td-move')) {
            e.preventDefault();
            pindahkanKartu(moveBtn.getAttribute('data-task'), moveBtn.getAttribute('data-move'));
        }
    });

    /* ---------- Drag & Drop kartu antar kolom terbuka ---------- */
    var draggedId = null;

    document.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.task-card');
        if (!card) return;
        draggedId = card.getAttribute('data-task');
        card.classList.add('dragging');
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', draggedId); } catch (err) {}
        }
    });

    document.addEventListener('dragend', function () {
        draggedId = null;
        document.querySelectorAll('.task-card.dragging').forEach(function (c) { c.classList.remove('dragging'); });
        document.querySelectorAll('.kc-body.drag-over').forEach(function (b) { b.classList.remove('drag-over'); });
    });

    board.addEventListener('dragover', function (e) {
        var body = e.target.closest('.kc-body');
        if (!body) return;
        e.preventDefault();
        if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
        body.classList.add('drag-over');
    });

    board.addEventListener('dragleave', function (e) {
        var body = e.target.closest('.kc-body');
        if (body && !body.contains(e.relatedTarget)) body.classList.remove('drag-over');
    });

    board.addEventListener('drop', function (e) {
        var body = e.target.closest('.kc-body');
        if (!body) return;
        e.preventDefault();
        body.classList.remove('drag-over');
        var target = body.getAttribute('data-drop-kolom');
        var id = draggedId || (e.dataTransfer ? e.dataTransfer.getData('text/plain') : null);
        if (!id || !target) return;

        var card = document.querySelector('.task-card[data-task="' + id + '"]');
        var asal = card ? card.closest('.kanban-col').getAttribute('data-kolom') : null;
        if (asal === target) return;

        pindahkanKartu(id, target);
    });

    /* ---------- Modal detail tugas via AJAX ---------- */
    document.addEventListener('click', function (e) {
        var detailBtn = e.target.closest('[data-task-detail]');
        if (!detailBtn) return;
        e.preventDefault();
        var taskId = detailBtn.getAttribute('data-task-detail');
        var body = document.getElementById('detailBody');
        body.innerHTML = '<p class="muted">Memuat detail tugas…</p>';
        window.tmOpenModal('modalDetail');

        fetch('/workspace/' + wsId + '/tugas/' + taskId + '/detail', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                body.classList.remove('modal-loading');
                body.innerHTML = html;
                body.classList.add('modal-loading');
            })
            .catch(function () {
                body.innerHTML = '<p class="muted">Gagal memuat detail tugas.</p>';
            });
    });

    // Tombol ubah status di dalam modal detail (dengan catatan opsional)
    document.addEventListener('click', function (e) {
        var move = e.target.closest('.td-move');
        if (!move) return;
        e.preventDefault();
        var catatanEl = document.getElementById('catatanStatus');
        var catatan = catatanEl ? catatanEl.value.trim() : '';
        pindahStatus(move.getAttribute('data-task'), move.getAttribute('data-move'), catatan, function (ok) {
            if (ok) setTimeout(function () { window.location.reload(); }, 500);
        });
    });

    // Tombol "Ubah Tugas" di modal detail: isi form modalTugas dengan data tugas
    document.addEventListener('click', function (e) {
        var editBtn = e.target.closest('[data-task-edit]');
        if (!editBtn) return;
        var taskId = editBtn.getAttribute('data-task-edit');
        var form = document.getElementById('formTugasBaru');
        if (!form) return;

        fetch('/workspace/' + wsId + '/tugas/' + taskId + '/edit', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                window.tmCloseModal('modalDetail');

                form.setAttribute('action', data.url_update);
                if (!form.querySelector('input[name="_method"]')) {
                    var m = document.createElement('input');
                    m.type = 'hidden';
                    m.name = '_method';
                    m.value = 'PATCH';
                    form.appendChild(m);
                } else {
                    form.querySelector('input[name="_method"]').value = 'PATCH';
                }
                form.querySelector('[name="judul"]').value = data.judul;
                form.querySelector('[name="deskripsi"]').value = data.deskripsi || '';
                form.querySelector('[name="id_penanggung_jawab"]').value = data.id_penanggung_jawab || '';
                form.querySelector('[name="deadline"]').value = data.deadline;

                var headBtn = document.querySelector('#modalTugas .modal-head h3');
                if (headBtn) headBtn.textContent = 'Ubah Tugas';

                window.tmOpenModal('modalTugas');
            });
    });
})();
