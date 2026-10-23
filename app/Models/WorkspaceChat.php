<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceChat extends Model
{
    protected $primaryKey = 'id_chat';

    protected $fillable = [
        'id_workspace',
        'id_user',
        'pesan',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_chat';
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
