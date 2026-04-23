<?php

namespace App\Models;

class Role extends DocumentModel
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'label',
        'permissions',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];
}
