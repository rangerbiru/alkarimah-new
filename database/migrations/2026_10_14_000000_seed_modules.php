<?php

use App\Constants\Modules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Modul yang dipakai kode. Hanya ID yang belum ada yang ditambahkan.
     * Kalau ID sudah dipakai modul dengan nama berbeda, migration berhenti agar
     * tidak menimpa atau menggeser data hak akses yang sudah ada.
     */
    private function modules(): array
    {
        return [
            Modules::CreateAbsence => ['Buat Absensi', 'Form tambah/buat absensi'],
            Modules::AdminAcademic => ['Kelola Akademik', 'Mengelola data akademik'],
            Modules::AdminHumanResource => ['Kelola Kepegawaian', 'Mengelola data kepegawaian Ibnu Abbas'],
            Modules::AdminInventory => ['Kelola Pengadaan Barang', 'Mengelola pengadaan barang pesantren'],
            Modules::KepalaSekolah => ['Kepala Sekolah', 'Laporan dan monitoring per jenjang'],
            Modules::WaliKelas => ['Wali Kelas', 'Data kelas yang diampu'],
            Modules::Kasir => ['Kasir', 'Tagihan, transaksi, pending, dan laporan keuangan'],
            Modules::KasirUnit => ['Kasir Unit', 'Transaksi, saldo, dan tabungan per jenjang (tanpa Pending)'],
            Modules::Bendahara => ['Bendahara', 'Verifikasi transaksi, payroll, dan tunjangan'],
            Modules::PenanggungJawabTabungan => ['Penanggung Jawab Tabungan', 'Pengambilan dan riwayat tabungan'],
        ];
    }

    public function up(): void
    {
        $existing = DB::table('module')->pluck('name', 'id');

        foreach ($this->modules() as $id => [$name, $description]) {
            if (! $existing->has($id)) {
                continue;
            }

            if ($existing[$id] !== $name) {
                throw new RuntimeException("Seed modul dibatalkan: ID {$id} sudah dipakai modul \"{$existing[$id]}\" (diharapkan \"{$name}\"). Selesaikan dulu agar hak akses yang ada tidak tertukar.");
            }
        }

        foreach ($this->modules() as $id => [$name, $description]) {
            if (! $existing->has($id)) {
                DB::table('module')->insert(['id' => $id, 'name' => $name, 'description' => $description]);
            }
        }
    }

    /**
     * Modul yang sudah dipakai hak akses tidak dihapus; hanya modul baru yang
     * belum punya module_rights yang dicabut.
     */
    public function down(): void
    {
        $ids = array_keys($this->modules());

        DB::table('module')
            ->whereIn('id', $ids)
            ->whereNotIn('id', DB::table('module_rights')->select('id_module'))
            ->delete();
    }
};
