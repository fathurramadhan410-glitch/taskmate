<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public const STATUS = ['todo', 'in_progress', 'review', 'done'];

    protected $primaryKey = 'id_task';

    protected $fillable = [
        'id_workspace',
        'judul',
        'deskripsi',
        'status',
        'deadline',
        'id_penanggung_jawab',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id_task';
    }

    /* ============================ RELASI ============================ */

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'id_workspace', 'id_workspace');
    }

    public function penanggungJawab()
    {
        return $this->belongsTo(User::class, 'id_penanggung_jawab', 'id_user');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id_user');
    }

    public function logs()
    {
        return $this->hasMany(TaskStatusLog::class, 'id_task', 'id_task')->orderBy('id_log', 'desc');
    }

    public function files()
    {
        return $this->hasMany(FileDokumen::class, 'id_task', 'id_task');
    }

    /* =========================== HELPER ============================= */

    /** True jika melewati deadline dan belum Done */
    public function isTerlambat(): bool
    {
        return $this->status !== 'done' && $this->deadline->isPast();
    }

    /**
     * Kategori warna deadline sesuai SRS 3.1:
     * kuning untuk H-3 s.d. H-1, merah untuk hari-H dan terlambat.
     */
    public function kategoriDeadline(): string
    {
        if ($this->status === 'done') {
            return 'done';
        }

        $selisih = $this->selisihHari();

        if ($selisih < 0) {
            return 'merah'; // terlambat
        }
        if ($selisih === 0) {
            return 'merah'; // hari-H
        }
        if ($selisih <= 3) {
            return 'kuning'; // H-3 s.d. H-1
        }

        return 'netral';
    }

    /** Selisih hari: negatif = terlambat, 0 = hari ini, positif = H-n */
    public function selisihHari(): int
    {
        $today = now()->startOfDay();
        $dl = $this->deadline->copy()->startOfDay();

        // Ambil nilai absolut lalu beri tanda berdasarkan perbandingan,
        // agar aman untuk Carbon 2 maupun Carbon 3.
        $abs = (int) floor(abs($today->diffInDays($dl)));

        return $dl->lt($today) ? -$abs : $abs;
    }
}
