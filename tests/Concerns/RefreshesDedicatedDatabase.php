<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;

trait RefreshesDedicatedDatabase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshDatabaseUsingLaravel;
    }

    protected function refreshDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $isDedicatedMysql = $connection === 'mysql'
            && $database === 'backend_test_testing';
        $isEphemeralSqlite = $connection === 'sqlite'
            && $database === ':memory:';

        if (! app()->environment('testing') || (! $isDedicatedMysql && ! $isEphemeralSqlite)) {
            throw new LogicException(
                'Feature tests require the dedicated backend_test_testing MySQL database or SQLite :memory:.'
            );
        }

        $this->refreshDatabaseUsingLaravel();
    }
}
