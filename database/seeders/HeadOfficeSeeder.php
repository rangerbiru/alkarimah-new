<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HeadOfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::create([
            'name' => 'Head Office',
            'phone' => '085212223424',
            'email' => 'pesantrenibnuabbas@gmail.com',
            'address' => 'Beku  Kliwonan, Kec. Masaran, Kab Sragen,  Propinsi Jawa Tengah Indonesia'
        ]);
    }
}
