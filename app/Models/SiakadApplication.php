<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiakadApplication extends Model
{
    protected $table = 'siakad_applications';

    protected $fillable = [
        'nama',
        'tag',
        'warna',
        'caption',
        'url',
        'logo',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}