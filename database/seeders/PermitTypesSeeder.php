<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermitTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('permit_types')->insert([
            [
                'permit_type' => 'Izin Terlambat Masuk',
                'level' => 1,
                'description' => 'Tetap masuk kerja, terlambat dengan alasan jelas',
                'wage_status' => 'y',
            ],
            [
                'permit_type' => 'Izin Harian',
                'level' => 2,
                'description' => 'Meninggalkan kerja sebelum jam selesai',
                'wage_status' => 'y',
            ],
            [
                'permit_type' => 'Izin Keluar Sementara',
                'level' => 1,
                'description' => 'Keluar kantor lalu kembali bekerja',
                'wage_status' => 'y',
            ],
            [
                'permit_type' => 'Izin Setengah Hari',
                'level' => 1,
                'description' => 'Tidak masuk separuh jam kerja',
                'wage_status' => 'y',
            ],
        ]);
    }
}
