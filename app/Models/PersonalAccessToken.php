<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use Laravel\Sanctum\PersonalAccessToken as SanctumToken;
use MongoDB\Laravel\Eloquent\DocumentModel;

class PersonalAccessToken extends SanctumToken
{
    use DocumentModel;
    use UsesUuidPrimaryKey;

    protected $connection = 'mongodb';

    protected $table = 'personal_access_tokens';

    protected $guarded = [];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
