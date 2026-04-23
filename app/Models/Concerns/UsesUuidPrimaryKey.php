<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait UsesUuidPrimaryKey
{
    protected static function bootUsesUuidPrimaryKey(): void
    {
        static::creating(function ($model): void {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function initializeUsesUuidPrimaryKey(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }
}
