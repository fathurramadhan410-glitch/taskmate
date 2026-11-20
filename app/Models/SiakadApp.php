<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Aplikasi kampus yang terhubung di halaman SIAKAD & E-Learning
 * (mis. Edlink Sevima, SIP Poliban). Dikelola lewat menu Administrator.
 */
class SiakadApp extends Model
{
    protected $primaryKey = 'id_siakad_app';

    protected $table = 'siakad_apps';

    protected $fillable = [
        'nama',
        'url',
        'deskripsi',
        'tag',
        'warna_tag',
        'logo',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'id_siakad_app';
    }

    /** URL lengkap logo: hasil unggahan admin (images/siakad/) atau file bawaan (images/...) */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        return Str::startsWith($this->logo, ['http://', 'https://', 'images/'])
            ? asset($this->logo)
            : asset('images/siakad/'.$this->logo);
    }

    /** Inisial untuk logo fallback (huruf pertama nama aplikasi) */
    public function getInisialAttribute(): string
    {
        return strtoupper(mb_substr(trim($this->nama), 0, 1));
    }
}