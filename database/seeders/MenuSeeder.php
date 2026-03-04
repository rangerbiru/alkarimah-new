<?php

namespace Database\Seeders;

use App\Enums\GroupMenu;
use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            [ // id: 1
                'id_parent' => null,
                'name' => 'Dashboard',
                'icon' => 'bx bx-bar-chart-alt-2',
                'route' => ['name' => 'dashboard.index', 'params' => []],
                'group' => GroupMenu::None,
                'sort' => 1,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk menampilkan dashboard'
            ],
            [ // id: 2
                'id_parent' => null,
                'name' => 'Cabang',
                'icon' => 'bx bx-building',
                'route' => ['name' => 'branch.index', 'params' => []],
                'group' => GroupMenu::None,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 2,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data cabang'
            ],
            [ // id: 3
                'id_parent' => null,
                'name' => 'Pengguna',
                'icon' => 'bx bx-user',
                'route' => null,
                'group' => GroupMenu::None,
                'sort' => 3,
                'is_parent' => true,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data pengguna'
            ],
            [ // id: 4
                'id_parent' => 3,
                'name' => 'Bendahara',
                'icon' => null,
                'route' => ['name' => 'user.index', 'params' => ['role' => 'bendahara']],
                'group' => GroupMenu::None,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 1,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data pengguna bendahara'
            ],
            [ // id: 5
                'id_parent' => 3,
                'name' => 'Kasir',
                'icon' => null,
                'route' => ['name' => 'user.index', 'params' => ['role' => 'kasir']],
                'group' => GroupMenu::None,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 2,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data pengguna kasir'
            ],
            [ // id: 6
                'id_parent' => 3,
                'name' => 'Wali Kelas',
                'icon' => null,
                'route' => ['name' => 'user.index', 'params' => ['role' => 'wali-kelas']],
                'group' => GroupMenu::None,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 3,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data wali kelas'
            ],
            [ // id: 7
                'id_parent' => 3,
                'name' => 'Penanggung Jawab',
                'icon' => null,
                'route' => ['name' => 'user.index', 'params' => ['role' => 'penanggung-jawab']],
                'group' => GroupMenu::None,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 4,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data pengguna penanggung jawab'
            ],
            [ // id: 8
                'id_parent' => null,
                'name' => 'Kelas',
                'icon' => 'bx bx-buildings',
                'route' => ['name' => 'academic.class.index'],
                'group' => GroupMenu::Akademik,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 4,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data kelas'
            ],
            [ // id: 9
                'id_parent' => null,
                'name' => 'Orang Tua',
                'icon' => 'bx bx-user',
                'route' => ['name' => 'academic.parent.index'],
                'group' => GroupMenu::Akademik,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 5,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data orang tua'
            ],
            [ // id: 10
                'id_parent' => null,
                'name' => 'Siswa',
                'icon' => 'bx bx-user',
                'route' => ['name' => 'academic.student.index'],
                'group' => GroupMenu::Akademik,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 6,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data siswa'
            ],
            [ // id: 11
                'id_parent' => null,
                'name' => 'Tagihan',
                'icon' => 'bx bx-credit-card-front',
                'route' => null,
                'group' => GroupMenu::Keuangan,
                'sort' => 7,
                'is_parent' => true,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola tagihan'
            ],
            [ // id: 12
                'id_parent' => 11,
                'name' => 'Jenis',
                'icon' => null,
                'route' => ['name' => 'finance.bill.type.index'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 1,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data jenis tagihan'
            ],
            [ // id: 13
                'id_parent' => 11,
                'name' => 'Setup',
                'icon' => null,
                'route' => ['name' => 'finance.bill.index'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 2,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengatur tagihan'
            ],
            [ // id: 14
                'id_parent' => null,
                'name' => 'Transaksi',
                'icon' => 'bx bx-receipt',
                'route' => null,
                'group' => GroupMenu::Keuangan,
                'sort' => 8,
                'is_parent' => true,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola transaksi'
            ],
            [ // id: 15
                'id_parent' => 14,
                'name' => 'Tagihan',
                'icon' => null,
                'route' => ['name' => 'finance.transaction.bill'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['payment', 'history'],
                'sort' => 1,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data tagihan'
            ],
            [ // id: 16
                'id_parent' => 14,
                'name' => 'Setoran Kas',
                'icon' => null,
                'route' => ['name' => 'finance.transaction.deposit'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['view', 'create', 'edit', 'delete', 'approval'],
                'sort' => 2,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data setoran kas'
            ],
            [ // id: 17
                'id_parent' => null,
                'name' => 'Tabungan',
                'icon' => 'bx bx-wallet',
                'route' => null,
                'group' => GroupMenu::Keuangan,
                'sort' => 9,
                'is_parent' => true,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola tabungan'
            ],
            [ // id: 18
                'id_parent' => 17,
                'name' => 'Setoran',
                'icon' => null,
                'route' => ['name' => 'finance.savings.deposit'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['deposit', 'history'],
                'sort' => 1,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data setoran tabungan'
            ],
            [ // id: 19
                'id_parent' => 17,
                'name' => 'Pengambilan',
                'icon' => null,
                'route' => ['name' => 'finance.savings.withdrawal'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['process', 'history'],
                'sort' => 2,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data pengambilan tabungan'
            ],
            [ // id: 20
                'id_parent' => null,
                'name' => 'Donasi',
                'icon' => 'bx bx-donate-heart',
                'route' => ['name' => 'finance.donation.index'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 10,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk mengelola data donasi'
            ],
            [ // id: 21
                'id_parent' => null,
                'name' => 'Laporan',
                'icon' => 'bx bx-file',
                'route' => null,
                'group' => GroupMenu::Keuangan,
                'sort' => 11,
                'is_parent' => true,
                'is_sidebar' => true,
                'description' => 'Fitur untuk melihat laporan keuangan'
            ],
            [ // id: 22
                'id_parent' => 21,
                'name' => 'Tagihan Belum Lunas',
                'icon' => null,
                'route' => ['name' => 'finance.report.bill-not-paid'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['view', 'download-pdf'],
                'sort' => 1,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk melihat laporan tagihan belum lunas'
            ],
            [ // id: 23
                'id_parent' => 21,
                'name' => 'Tagihan Per Siswa',
                'icon' => null,
                'route' => ['name' => 'finance.report.bill-student'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['view', 'download-pdf'],
                'sort' => 2,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk melihat laporan tagihan per siswa'
            ],
            [ // id: 24
                'id_parent' => 21,
                'name' => 'Donasi',
                'icon' => null,
                'route' => ['name' => 'finance.report.donation'],
                'group' => GroupMenu::Keuangan,
                'actions' => ['view', 'download-pdf'],
                'sort' => 3,
                'is_parent' => false,
                'is_sidebar' => true,
                'description' => 'Fitur untuk melihat laporan donasi'
            ],
            [ // id: 25
                'id_parent' => null,
                'name' => 'Pengaturan',
                'icon' => 'bx bx-cog',
                'route' => null,
                'group' => GroupMenu::None,
                'sort' => 1,
                'is_parent' => true,
                'is_sidebar' => false,
                'description' => 'Fitur untuk mengatur aplikasi'
            ],
            [ // id: 26
                'id_parent' => 25,
                'name' => 'Tahun Ajaran',
                'icon' => null,
                'route' => ['name' => 'setting.year'],
                'group' => GroupMenu::None,
                'actions' => ['view', 'create', 'edit', 'delete'],
                'sort' => 1,
                'is_parent' => false,
                'is_sidebar' => false,
                'description' => 'Fitur untuk mengatur tahun ajaran'
            ],
            [ // id: 27
                'id_parent' => null,
                'name' => 'Tabungan',
                'icon' => 'bx bx-wallet',
                'route' => ['name' => 'finance.savings.index'],
                'group' => GroupMenu::None,
                'actions' => ['view', 'deposit', 'history'],
                'sort' => 2,
                'is_parent' => false,
                'is_sidebar' => false,
                'description' => 'Fitur untuk mengelola tabungan'
            ],
            [ // id: 28
                'id_parent' => null,
                'name' => 'Pembayaran',
                'icon' => 'bx bx-credit-card-front',
                'route' => ['name' => 'finance.payment.index'],
                'group' => GroupMenu::None,
                'sort' => 3,
                'is_parent' => false,
                'is_sidebar' => false,
                'description' => 'Fitur untuk melakukan pembayaran tagihan'
            ],
        ];

        foreach ($menus as $m)
            Menu::create($m);
    }
}
