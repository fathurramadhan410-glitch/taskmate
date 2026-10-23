<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    /** Tabel notifikasis memakai kolom waktu_kirim, tanpa created_at/updated_at. */
    public $timestamps = false;

    public const JENIS = [
        'penugasan', 'perubahan', 'h3', 'h1', 'hari_h', 'terlambat', 'review', 'bergabung',
    ];

    protected $primaryKey = 'id_notif';

    protected $fillable = [
        'id_user',
        'id_task',
        'jenis',
        'pesan',
        'status_baca',
        'status_email',
        'waktu_kirim',
    ];

    protected function casts(): array
    {
        return [
            'status_baca' => 'boolean',
            'waktu_kirim' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id_notif';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'id_task', 'id_task');
    }
}
