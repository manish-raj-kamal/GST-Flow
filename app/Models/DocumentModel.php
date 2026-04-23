<?php

namespace App\Models;

use App\Models\Concerns\UsesUuidPrimaryKey;
use MongoDB\Laravel\Eloquent\Model;

abstract class DocumentModel extends Model
{
    use UsesUuidPrimaryKey;

    protected $connection = 'mongodb';
}
