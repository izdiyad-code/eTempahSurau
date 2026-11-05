<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Never prepare a schema against a persistent or application database.
        $connection = $this->app['db']->connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Tests require an isolated SQLite :memory: database.');
        }

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }
}
