<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'id_version';

    protected $fillable = [
        'id_file',
        'nomor_versi',
        'path_file',
        'ukuran_byte',
        'catatan_revisi',
        'id_uploader',
        'waktu_unggah',
    ];

    protected function casts(): array
    {
        return [
            'waktu_unggah' => 'datetime',
            'ukuran_byte' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id_version';
    }

    public function file()
    {
        return $this->belongsTo(FileDokumen::class, 'id_file', 'id_file');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'id_uploader', 'id_user');
    }

    public function ekstensi(): string
    {
        return strtolower(pathinfo($this->file->nama_file ?? '', PATHINFO_EXTENSION));
    }
}
