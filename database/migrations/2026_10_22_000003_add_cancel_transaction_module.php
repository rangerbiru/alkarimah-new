<?php

use App\Constants\Modules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAME = 'Pembatalan Transaksi';

    private const DESCRIPTION = 'Membatalkan transaksi pembayaran masuk yang dibuat orang tua';

    /**
     * Modul baru (ID Modules::CancelTransaction). Tidak ada module_rights baru: sampai hak diberikan
     * dari halaman Hak Akses pegawai, hanya admin yang bisa memakai fitur ini.
     * ID yang sudah dipakai modul lain menghentikan migration; tidak ada INSERT IGNORE
     * agar hak akses yang ada tidak tertukar.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $row = DB::table('module')->where('id', Modules::CancelTransaction)->lockForUpdate()->first();

            if ($row === null) {
                DB::table('module')->insert([
                    'id' => Modules::CancelTransaction,
                    'name' => self::NAME,
                    'description' => self::DESCRIPTION,
                ]);

                return;
            }

            if (! $this->isCancelModule($row->name)) {
                throw new RuntimeException(
                    'Migration dibatalkan: ID modul '.Modules::CancelTransaction.' sudah dipakai modul "'.$row->name.'". '.
                    'Ubah Modules::CancelTransaction ke ID yang belum dipakai (SELECT id, name FROM module), '.
                    'lalu jalankan ulang migration ini.'
                );
            }
        });
    }

    /**
     * Hanya menghapus modul miliknya sendiri (beserta module_rights-nya).
     */
    public function down(): void
    {
        DB::transaction(function () {
            $row = DB::table('module')->where('id', Modules::CancelTransaction)->lockForUpdate()->first();

            if ($row === null || ! $this->isCancelModule($row->name)) {
                return;
            }

            DB::table('module_rights')->where('id_module', Modules::CancelTransaction)->delete();
            DB::table('module')->where('id', Modules::CancelTransaction)->delete();
        });
    }

    private function isCancelModule(string $name): bool
    {
        return mb_strtolower(trim($name)) === mb_strtolower(self::NAME);
    }
};
