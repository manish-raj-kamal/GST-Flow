<?php

namespace App\Models;

class Permission extends DocumentModel
{
    protected $table = 'permissions';

    protected $fillable = [
        'code',
        'label',
        'description',
        'group',
    ];
}
