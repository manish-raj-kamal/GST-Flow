<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshMongoTestDatabase();
    }

    private function refreshMongoTestDatabase(): void
    {
        if (config('database.default') !== 'mongodb') {
            return;
        }

        $connection = DB::connection('mongodb');
        $databaseName = $connection->getDatabaseName();

        if (! str_contains($databaseName, 'test')) {
            throw new RuntimeException("Refusing to clear non-test MongoDB database [{$databaseName}].");
        }

        $database = $connection->getDatabase();

        foreach ($database->listCollections() as $collection) {
            $database->dropCollection($collection->getName());
        }
    }
}
