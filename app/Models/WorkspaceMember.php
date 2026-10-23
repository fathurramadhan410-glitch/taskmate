<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceMember extends Model
{
    /** Tabel workspace_members memakai kolom tanggal_bergabung, tanpa created_at/updated_at. */
    public $timestamps = false;

    protected $primaryKey = 'id_member';

    protected $table = 'workspace_members';

    public function getRouteKeyName(): string
    {
        return 'id_member';
    }

    protected $fillable = [
        'id_workspace',
        'id_user',
        'peran',
        'tanggal_bergabung',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bergabung' => 'datetime',
        ];
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'id_workspace', 'id_workspace');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
