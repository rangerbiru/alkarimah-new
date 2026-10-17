<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private const TEST_DATABASE = 'app_alkarimah_testing';

    /**
     * Pengaman: aplikasi tidak boot bila koneksi utama maupun mysql_second bukan DB testing.
     */
    public function createApplication()
    {
        $app = parent::createApplication();
        $config = $app['config'];

        foreach ([$config->get('database.default'), 'mysql_second'] as $connection) {
            $database = $config->get("database.connections.{$connection}.database");

            if ($database !== self::TEST_DATABASE) {
                throw new RuntimeException(
                    "Test dibatalkan: koneksi [{$connection}] memakai database [{$database}], bukan [".self::TEST_DATABASE.']. '.
                    'Pastikan .env.testing ada dan DB_DATABASE / DB_SECOND_DATABASE = '.self::TEST_DATABASE.'.'
                );
            }
        }

        return $app;
    }

    /**
     * Pengaman utama: setiap test gagal bila DB aktif bukan DB testing.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $database = DB::connection()->getDatabaseName();

        if ($database !== 'app_alkarimah_testing') {
            throw new RuntimeException("Test menolak berjalan di database '{$database}', hanya app_alkarimah_testing yang diizinkan.");
        }
    }

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
