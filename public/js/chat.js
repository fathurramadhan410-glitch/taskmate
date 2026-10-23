/* ==========================================================================
   TaskMate — chat.js
   Chat realtime sederhana berbasis polling AJAX (aman untuk XAMPP lokal,
   tanpa WebSocket). Pesan baru otomatis muncul tiap 2 detik.
   ========================================================================== */

(function () {
    'use strict';

    var wrap = document.querySelector('.chat-wrap');
    if (!wrap) return;

    var wsId = wrap.getAttribute('data-ws');
    var lastId = parseInt(wrap.getAttribute('data-last') || '0', 10);

    var body = document.getElementById('chatBody');
    var form = document.getElementById('chatForm');
    var text = document.getElementById('chatText');
    var sendBtn = document.getElementById('chatSend');

    /* ---------- Util ---------- */
    function buatAvatar(user) {
        var span = document.createElement('span');
        span.className = 'avatar avatar-xs avatar-fallback';
        span.textContent = user.initial || '?';
        return span;
    }

    function buatBubble(msg) {
        var row = document.createElement('div');
        row.className = 'chat-msg' + (msg.own ? ' own' : '');
        row.setAttribute('data-id', msg.id);

        if (!msg.own) {
            row.appendChild(buatAvatar(msg.user));
        }

        var bubble = document.createElement('div');
        bubble.className = 'bubble' + (msg.own ? ' bubble-own' : '');

        if (!msg.own) {
            var nama = document.createElement('span');
            nama.className = 'bubble-name';
            nama.textContent = msg.user.nama; // textContent = aman dari XSS
            bubble.appendChild(nama);
        }

        var teks = document.createElement('p');
        teks.className = 'bubble-text';
        teks.textContent = msg.pesan;
        bubble.appendChild(teks);

        var waktu = document.createElement('span');
        waktu.className = 'bubble-time';
        waktu.textContent = msg.tanggal + ' ' + msg.waktu;
        bubble.appendChild(waktu);

        row.appendChild(bubble);
        return row;
    }

    function hapusEmptyState() {
        var empty = body.querySelector('.chat-empty');
        if (empty) empty.remove();
    }

    function dekatBawah() {
        return body.scrollHeight - body.scrollTop - body.clientHeight < 120;
    }

    function gulirBawah(smooth) {
        body.scrollTo({ top: body.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
    }

    /* ---------- Polling pesan baru ---------- */
    function polling() {
        fetch('/workspace/' + wsId + '/chat/pesan?after=' + lastId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.ok ? r.json() : { messages: [] }; })
            .then(function (data) {
                if (data.messages && data.messages.length > 0) {
                    var diBawah = dekatBawah();
                    data.messages.forEach(function (msg) {
                        if (msg.id > lastId) {
                            // hindari duplikat (mis. pesan sendiri yang sudah dirender)
                            if (!body.querySelector('.chat-msg[data-id="' + msg.id + '"]')) {
                                hapusEmptyState();
                                body.appendChild(buatBubble(msg));
                            }
                            lastId = Math.max(lastId, msg.id);
                        }
                    });
                    if (diBawah) gulirBawah(true);
                }
            })
            .catch(function () { /* koneksi lokal terputus: coba lagi nanti */ });
    }

    setInterval(polling, 2000);

    /* ---------- Kirim pesan ---------- */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var pesan = text.value.trim();
        if (!pesan) return;

        sendBtn.disabled = true;

        var formData = new FormData();
        formData.append('pesan', pesan);
        formData.append('_token', window.tmCsrf);

        fetch('/workspace/' + wsId + '/chat', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.tmCsrf
            },
            body: formData
        })
            .then(function (r) {
                return r.json().catch(function () { return { message: null }; })
                    .then(function (data) { return { ok: r.ok, data: data }; });
            })
            .then(function (res) {
                if (res.ok && res.data.message) {
                    var msg = res.data.message;
                    if (!body.querySelector('.chat-msg[data-id="' + msg.id + '"]')) {
                        hapusEmptyState();
                        body.appendChild(buatBubble(msg));
                        lastId = Math.max(lastId, msg.id);
                    }
                    text.value = '';
                    text.style.height = 'auto';
                    gulirBawah(true);
                } else {
                    window.tmToast((res.data && res.data.message) ? res.data.message : 'Pesan gagal terkirim.', 'gagal');
                }
            })
            .catch(function () {
                window.tmToast('Koneksi bermasalah. Pesan tidak terkirim.', 'gagal');
            })
            .finally(function () {
                sendBtn.disabled = false;
                text.focus();
            });
    });

    /* ---------- Enter kirim, Shift+Enter baris baru, textarea tumbuh ---------- */
    text.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit'));
        }
    });

    text.addEventListener('input', function () {
        text.style.height = 'auto';
        text.style.height = Math.min(text.scrollHeight, 110) + 'px';
    });

    /* ---------- Inisialisasi tampilan ---------- */
    gulirBawah(false);
})();
