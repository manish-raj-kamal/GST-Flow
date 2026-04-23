<?php

namespace App\Models;

class StateCode extends DocumentModel
{
    protected $table = 'state_codes';

    protected $fillable = [
        'code',
        'state_name',
    ];
}
