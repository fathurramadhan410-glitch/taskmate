<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileDokumen extends Model
{
    /** Berkas pekerjaan tugas (draf) */
    public const KATEGORI_DRAF = 'draf';

    /** Materi / referensi — hanya diunggah Project Leader */
    public const KATEGORI_MATERI = 'materi';

    protected $primaryKey = 'id_file';

    protected $fillable = [
        'id_workspace',
        'id_task',
        'kategori',
        'nama_file',
        'dibuat_oleh',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_file';
    }

    /* ============================ RELASI ============================ */

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'id_workspace', 'id_workspace');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'id_task', 'id_task');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id_user');
    }

    public function versions()
    {
        return $this->hasMany(FileVersion::class, 'id_file', 'id_file')->orderByDesc('nomor_versi');
    }

    /* ============================ SCOPE ============================= */

    public function scopeDraf($query)
    {
        return $query->where('kategori', self::KATEGORI_DRAF);
    }

    public function scopeMateri($query)
    {
        return $query->where('kategori', self::KATEGORI_MATERI);
    }

    /* =========================== HELPER ============================= */

    public function isMateri(): bool
    {
        return $this->kategori === self::KATEGORI_MATERI;
    }

    public function versiTerbaru(): ?FileVersion
    {
        return $this->versions()->first();
    }

    public function ekstensi(): string
    {
        return strtolower(pathinfo($this->nama_file, PATHINFO_EXTENSION));
    }

    /** 'pdf' | 'image' | null (null = format tidak mendukung pratinjau) */
    public function tipePratinjau(): ?string
    {
        return preview_type($this->ekstensi());
    }
}