<?php

namespace Tests\Feature\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembantu tes render halaman edit: isi baris tabel secara generik dan cek select / radio pada HTML.
 */
trait RendersFormValues
{
    /**
     * Sisipkan satu baris; kolom wajib tanpa default diisi nilai aman menurut tipenya.
     * FK dimatikan sementara agar baris induk tidak perlu dibuat satu per satu.
     */
    protected function insertRow(string $table, array $values = []): int
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $row = [];

        foreach (Schema::getColumns($table) as $column) {
            $name = $column['name'];

            if (array_key_exists($name, $values)) {
                $row[$name] = $values[$name];

                continue;
            }

            if ($column['nullable'] || $column['default'] !== null || $column['auto_increment']) {
                continue;
            }

            $type = $column['type_name'];
            $row[$name] = match (true) {
                in_array($type, ['int', 'integer', 'bigint', 'smallint', 'mediumint', 'tinyint']) => 1,
                in_array($type, ['decimal', 'double', 'float']) => 0,
                in_array($type, ['date']) => '2026-01-01',
                in_array($type, ['datetime', 'timestamp']) => '2026-01-01 00:00:00',
                in_array($type, ['json']) => '[]',
                default => 'x',
            };
        }

        $id = DB::table($table)->insertGetId($row + array_diff_key($values, $row));

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return (int) $id;
    }

    /** Nilai option yang selected di dalam <select name="$name"> */
    protected function selectedIn(string $html, string $name): array
    {
        $quoted = preg_quote($name, '/');

        if (! preg_match('/<select[^>]*\bname="'.$quoted.'"[^>]*>(.*?)<\/select>/s', $html, $m)) {
            $this->fail("Select [$name] tidak ada di halaman");
        }

        preg_match_all('/<option value="([^"]*)"\s+selected/', $m[1], $options);

        return $options[1];
    }

    /** Nilai radio yang checked untuk name tertentu */
    protected function checkedIn(string $html, string $name): array
    {
        $quoted = preg_quote($name, '/');
        preg_match_all('/<input type="radio"[^>]*\bvalue="([^"]*)"[^>]*\bname="'.$quoted.'"[^>]*\bchecked/', $html, $m);

        if (! preg_match('/<input type="radio"[^>]*\bname="'.$quoted.'"/', $html)) {
            $this->fail("Radio [$name] tidak ada di halaman");
        }

        return $m[1];
    }
}
