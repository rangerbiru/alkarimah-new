<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman RefreshDatabase: jangan pernah migrate:fresh di luar DB testing
     * (phpunit.xml -> DB_DATABASE=app_alkarimah_testing).
     */
    protected function beforeRefreshingDatabase()
    {
        $database = DB::connection()->getDatabaseName();

        if ($database !== 'app_alkarimah_testing') {
            throw new RuntimeException("Test menolak refresh database '{$database}', hanya app_alkarimah_testing yang diizinkan.");
        }
    }
}
