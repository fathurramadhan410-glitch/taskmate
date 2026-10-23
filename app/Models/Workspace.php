<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Workspace extends Model
{
    protected $primaryKey = 'id_workspace';

    protected $fillable = [
        'nama_workspace',
        'mata_kuliah',
        'deskripsi',
        'kode_undangan',
        'undangan_aktif',
        'undangan_berlaku_sampai',
        'id_pembuat',
    ];

    protected function casts(): array
    {
        return [
            'undangan_aktif' => 'boolean',
            'undangan_berlaku_sampai' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id_workspace';
    }

    /* ============================ RELASI ============================ */

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'id_pembuat', 'id_user');
    }

    public function members()
    {
        return $this->hasMany(WorkspaceMember::class, 'id_workspace', 'id_workspace');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'id_workspace', 'id_workspace');
    }

    public function files()
    {
        return $this->hasMany(FileDokumen::class, 'id_workspace', 'id_workspace');
    }

    public function chats()
    {
        return $this->hasMany(WorkspaceChat::class, 'id_workspace', 'id_workspace');
    }

    /* =========================== HELPER ============================= */

    /** Membuat kode undangan unik (tanpa karakter 0/O/1/I agar tidak membingungkan) */
    public static function buatKode(): string
    {
        $charset = config('taskmate.invite_charset');
        $panjang = (int) config('taskmate.invite_length', 8);

        do {
            $kode = '';
            for ($i = 0; $i < $panjang; $i++) {
                $kode .= $charset[random_int(0, strlen($charset) - 1)];
            }
        } while (self::where('kode_undangan', $kode)->exists());

        return $kode;
    }

    /** Link undangan bertanda tangan (signed URL) — tidak bisa dimanipulasi */
    public function inviteLink(): string
    {
        return URL::signedRoute('gabung.kode', ['kode' => $this->kode_undangan]);
    }

    public function leaderMember(): ?WorkspaceMember
    {
        return $this->members()->where('peran', 'leader')->with('user')->first();
    }

    public function hasMember(int $idUser): bool
    {
        return $this->members()->where('id_user', $idUser)->exists();
    }

    /** Apakah undangan sedang dapat digunakan */
    public function undanganValid(): bool
    {
        if (! $this->undangan_aktif) {
            return false;
        }

        return ! ($this->undangan_berlaku_sampai && now()->gt($this->undangan_berlaku_sampai));
    }
}
