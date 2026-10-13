<?php

namespace App\Console\Commands;

use App\Constants\Modules;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\ModuleRights;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Pemulihan akun kepala sekolah yang sebenarnya akun pegawai: role dikembalikan ke
 * `pegawai`, modul Kepala Sekolah diberikan lewat module_rights, dan jenjang akun
 * (module_id NULL) dipindah menjadi jenjang modul (module_id = Modules::KepalaSekolah).
 *
 * Tanpa --force hanya menampilkan rencana (sama dengan --dry-run). Akun yang namanya
 * terlihat seperti nomor HP ditolak dan hanya dilaporkan, tidak diperbaiki otomatis.
 */
class RestorePegawaiFromKepalaSekolahCommand extends Command
{
    protected $signature = 'access:restore-pegawai-from-kepala-sekolah
                            {--dry-run : Tampilkan rencana tanpa mengubah data}
                            {--force : Jalankan perubahan}';

    protected $description = 'Ubah akun role kepala-sekolah yang terhubung ke pegawai menjadi pegawai + modul Kepala Sekolah';

    public function handle(): int
    {
        $apply = $this->option('force') && ! $this->option('dry-run');

        if (! DB::table('module')->where('id', Modules::KepalaSekolah)->where('name', 'Kepala Sekolah')->exists()) {
            $message = 'Modul Kepala Sekolah (id '.Modules::KepalaSekolah.') belum ada di tabel module. Jalankan migration seed_modules lebih dulu.';

            if ($apply) {
                $this->error($message);

                return self::FAILURE;
            }

            $this->warn($message.' (--force akan menolak berjalan)');
        }

        $hasModuleColumn = Schema::hasColumn('user_education_levels', 'module_id');

        if (! $hasModuleColumn) {
            $message = 'Kolom user_education_levels.module_id belum ada. Jalankan migration add_module_id_to_user_education_levels_table lebih dulu.';

            if ($apply) {
                $this->error($message);

                return self::FAILURE;
            }

            $this->warn($message.' (--force akan menolak berjalan; semua baris jenjang dianggap jenjang akun)');
        }

        $this->line($apply ? 'MODE: JALANKAN PERUBAHAN (--force)' : 'MODE: DRY-RUN (tidak ada data yang diubah)');

        $users = User::withoutGlobalScopes()
            ->where('role', UserRole::KepalaSekolah->value)
            ->orderBy('id')
            ->get();

        $rows = ['ok' => [], 'rejected' => [], 'no_employee' => []];

        foreach ($users as $user) {
            $employee = Employee::withoutGlobalScopes()
                ->where('id_user', $user->id)
                ->whereNull('deleted_at')
                ->first();

            if ($this->looksLikePhoneNumber($user->name)) {
                $rows['rejected'][] = [$user->id, $user->name, $employee?->name ?? '-', 'nama user berupa nomor HP, perbaiki manual'];

                continue;
            }

            if (! $employee) {
                $rows['no_employee'][] = [$user->id, $user->name, '-', 'tidak terhubung ke pegawai'];

                continue;
            }

            $levels = DB::table('user_education_levels')
                ->where('user_id', $user->id)
                ->when($hasModuleColumn, fn ($q) => $q->whereNull('module_id'))
                ->pluck('level_education')
                ->all();
            $note = empty($levels) ? 'PERINGATAN: belum punya jenjang' : 'jenjang: '.implode(',', $levels);
            $rows['ok'][] = [$user->id, $user->name, $employee->name, $note];

            if ($apply) {
                $this->restore($user, $levels);
            }
        }

        $this->report('Akan diubah / diubah', $rows['ok']);
        $this->report('DITOLAK (nama mirip nomor HP, tidak diubah)', $rows['rejected']);
        $this->report('Dilewati (tidak terhubung pegawai)', $rows['no_employee']);

        $this->info(sprintf(
            'Ringkasan: %d %s, %d ditolak, %d dilewati.',
            count($rows['ok']),
            $apply ? 'diubah' : 'akan diubah',
            count($rows['rejected']),
            count($rows['no_employee'])
        ));

        if (! $apply && ! $this->option('dry-run')) {
            $this->warn('Tidak ada perubahan. Tambahkan --force untuk menjalankan.');
        }

        return self::SUCCESS;
    }

    private function restore(User $user, array $levels): void
    {
        DB::transaction(function () use ($user, $levels) {
            DB::table('users')->where('id', $user->id)->update(['role' => UserRole::Pegawai->value]);

            ModuleRights::firstOrCreate(['id_module' => Modules::KepalaSekolah, 'id_user' => $user->id]);

            // Jenjang akun -> jenjang modul (hindari bentrok unique user_id+module_id+level)
            foreach ($levels as $level) {
                DB::table('user_education_levels')->updateOrInsert(
                    ['user_id' => $user->id, 'module_id' => Modules::KepalaSekolah, 'level_education' => $level],
                    ['updated_at' => now()]
                );
            }

            DB::table('user_education_levels')->where('user_id', $user->id)->whereNull('module_id')->delete();
        });

        Log::info('access:restore-pegawai-from-kepala-sekolah', [
            'user_id' => $user->id,
            'name' => $user->name,
            'levels' => $levels,
        ]);
    }

    /**
     * Nama berisi nomor HP (angka, boleh diawali +, spasi, tanda hubung), minimal 8 digit.
     */
    private function looksLikePhoneNumber(?string $name): bool
    {
        $name = trim((string) $name);

        return $name !== ''
            && preg_match('/^\+?[\d\s\-().]+$/', $name) === 1
            && strlen(preg_replace('/\D/', '', $name)) >= 8;
    }

    private function report(string $title, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $this->newLine();
        $this->line("<options=bold>{$title}</>");
        $this->table(['user_id', 'nama user', 'nama pegawai', 'catatan'], $rows);
    }
}
