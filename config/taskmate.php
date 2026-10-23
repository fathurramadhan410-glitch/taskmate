<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Khusus TaskMate
|--------------------------------------------------------------------------
| Nilai-nilai batasan yang diambil dari dokumen SRS TaskMate (Kelompok 5,
| Politeknik Negeri Banjarmasin, 2026).
*/

return [

    // Ukuran maksimal berkas per unggahan dalam KB (20480 KB = 20 MB) - SRS 2.5.d
    'max_file_kb' => (int) env('TASKMATE_MAX_FILE_KB', 20480),

    // Daftar ekstensi berkas yang diizinkan - SRS 2.5.d
    'allowed_file_mimes' => 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,png,jpg,jpeg',

    // Maksimal percobaan login gagal sebelum akun dikunci - SRS FR-003
    'max_login_attempts' => 5,

    // Durasi penguncian akun dalam menit - SRS UC-01
    'lock_minutes' => 15,

    // Charset pembuat kode undangan (tanpa karakter membingungkan: 0/O/1/I)
    'invite_charset' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',

    // Panjang kode undangan
    'invite_length' => 8,

];
