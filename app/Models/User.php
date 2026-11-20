<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'google_id',
        'avatar',
        'role',
        'status_akun',
        'blokir_alasan',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'terkunci_sampai' => 'datetime',
            'gagal_login' => 'integer',
        ];
    }

    /* ============================ RELASI ============================ */

    public function workspacesDibuat()
    {
        return $this->hasMany(Workspace::class, 'id_pembuat', 'id_user');
    }

    public function memberships()
    {
        return $this->hasMany(WorkspaceMember::class, 'id_user', 'id_user');
    }

    public function tasksDitanggung()
    {
        return $this->hasMany(Task::class, 'id_penanggung_jawab', 'id_user');
    }

    public function notifikasis()
    {
        return $this->hasMany(Notifikasi::class, 'id_user', 'id_user');
    }

    public function chats()
    {
        return $this->hasMany(WorkspaceChat::class, 'id_user', 'id_user');
    }

    /* =========================== HELPER ============================= */

    /** Peran pengguna pada sebuah workspace: 'leader' | 'member' | null */
    public function roleIn(int $workspaceId): ?string
    {
        return WorkspaceMember::where('id_workspace', $workspaceId)
            ->where('id_user', $this->id_user)
            ->value('peran');
    }

    /** Apakah akun dibuat melalui Google (tanpa kata sandi lokal) */
    public function isGoogleOnly(): bool
    {
        return is_null($this->password);
    }

    /** Peran sistem: 'admin' | 'anggota' */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Akun diblokir manual oleh administrator (tidak bisa login sampai dibuka) */
    public function isDiblokir(): bool
    {
        return $this->status_akun === 'diblokir';
    }

    /** Akun terkunci otomatis karena gagal login berulang (FR-003) */
    public function isTerkunci(): bool
    {
        return $this->terkunci_sampai !== null && now()->lt($this->terkunci_sampai);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        return Str::startsWith($this->avatar, ['http://', 'https://'])
            ? $this->avatar
            : asset('storage/'.$this->avatar);
    }

    /** Jumlah notifikasi yang belum dibaca */
    public function unreadCount(): int
    {
        return $this->notifikasis()->where('status_baca', false)->count();
    }
}