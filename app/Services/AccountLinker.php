<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Parents;
use App\Models\Scopes\BranchScope;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Menghubungkan baris orang tua (parent) ke akun pegawai, sehingga satu user adalah pegawai SEKALIGUS
 * orang tua (employee.id_user = parent.id_user). Dipicu admin, tidak pernah otomatis.
 *
 * link():    parent.id_user -> user pegawai. Bila orang tua punya akun sendiri (role orang-tua), kolom
 *            referensi di config/account-link.php dipindah ke user pegawai lalu akun lama di-soft-delete.
 *            parent.phone disamakan dengan users.phone pegawai. Data rollback disimpan di account_link_log.
 * unlink():  mengembalikan kondisi dari entri rollback.
 * preview(): hanya baca: apa yang akan terjadi dan apa yang menghalangi.
 *
 * Password tidak pernah diubah: orang tua yang digabung login dengan password akun pegawai.
 *
 * Batasan rollback: baris yang dibuat user pegawai SETELAH penggabungan tidak bisa dibedakan, sehingga
 * tetap milik user pegawai setelah unlink().
 */
class AccountLinker
{
    public function preview(?int $employeeId, int $parentId): array
    {
        $state = $this->inspect($employeeId, $parentId, false);

        $children = $state->parent
            ? Student::withoutGlobalScope(BranchScope::class)
                ->with('class:id,name')
                ->where('id_parent', $parentId)
                ->orderBy('name')
                ->get(['id', 'id_class', 'name', 'nis'])
                ->map(fn ($s) => ['name' => $s->name, 'nis' => $s->nis, 'class' => $s->class?->name ?? '-'])
                ->all()
            : [];

        $moved = [];

        if ($state->old_user) {
            foreach ($this->columns() as [$table, $column, $extra]) {
                $count = $this->referenceQuery($table, $column, $extra, $state->old_user->id)->count();

                if ($count > 0) {
                    $moved["{$table}.{$column}"] = $count;
                }
            }
        }

        return [
            'can_link' => empty($state->blockers),
            'blockers' => $state->blockers,
            'warnings' => $state->warnings,
            'employee' => $state->employee ? [
                'id' => $state->employee->id, 'name' => $state->employee->name,
                'nip' => $state->employee->nip, 'phone' => $state->employee->phone,
            ] : null,
            'parent' => $state->parent ? [
                'id' => $state->parent->id, 'name' => $state->parent->name, 'phone' => $state->parent->phone,
            ] : null,
            'old_user' => $state->old_user ? [
                'id' => $state->old_user->id, 'name' => $state->old_user->name,
                'email' => $state->old_user->email, 'phone' => $state->old_user->phone,
            ] : null,
            'children' => $children,
            'moved' => $moved,
            'moved_rows' => array_sum($moved),
            'new_parent_phone' => $state->employee_user?->phone,
        ];
    }

    /**
     * @return array{link_id: int, moved_rows: int, old_user_id: int|null}
     *
     * @throws AccountLinkException
     */
    public function link(int $employeeId, int $parentId): array
    {
        return DB::transaction(function () use ($employeeId, $parentId) {
            // Kunci semua baris yang terlibat sebelum memeriksa pemblokir
            $parent = Parents::withoutGlobalScope(BranchScope::class)->lockForUpdate()->find($parentId);
            $employee = Employee::withoutGlobalScopes()->lockForUpdate()->find($employeeId);
            $userIds = array_filter([$employee?->id_user, $parent?->id_user]);
            $userIds && User::withoutGlobalScopes()->withTrashed()->whereIn('id', $userIds)->lockForUpdate()->get();

            $state = $this->inspect($employeeId, $parentId, true);

            if (! empty($state->blockers)) {
                throw new AccountLinkException($state->blockers[0]['message'], $state->blockers);
            }

            $employeeUser = $state->employee_user;
            $oldUser = $state->old_user;
            $before = [
                'parent_id_user' => $parent->id_user,
                'parent_phone' => $parent->phone,
                'old_user_deleted' => $oldUser !== null,
            ];

            $moved = [];

            if ($oldUser) {
                foreach ($this->columns() as [$table, $column, $extra]) {
                    $ids = $this->referenceQuery($table, $column, $extra, $oldUser->id)->pluck('id')->all();

                    if ($ids) {
                        DB::table($table)->whereIn('id', $ids)->update([$column => $employeeUser->id]);
                        $moved["{$table}.{$column}"] = $ids;
                    }
                }

                // Sesi aktif akun lama tidak boleh dipakai lagi
                DB::table('sessions')->where('user_id', $oldUser->id)->delete();
            }

            $parent->id_user = $employeeUser->id;
            $parent->phone = $employeeUser->phone;
            $parent->saveQuietly();

            $oldUser?->delete();

            $logId = DB::table('account_link_log')->insertGetId([
                'action' => 'link',
                'employee_id' => $employee->id,
                'parent_id' => $parent->id,
                'employee_user_id' => $employeeUser->id,
                'old_user_id' => $oldUser?->id,
                'actor_id' => Auth::id(),
                'before' => json_encode($before),
                'moved' => json_encode($moved),
            ]);

            $this->audit('link', $parent, $employee, $employeeUser, $oldUser, array_sum(array_map('count', $moved)));

            return [
                'link_id' => $logId,
                'moved_rows' => array_sum(array_map('count', $moved)),
                'old_user_id' => $oldUser?->id,
            ];
        });
    }


    /**
     * Preview untuk santri yang belum punya data orang tua (id_parent kosong): link akan membuat data
     * orang tua baru dari data pegawai (nama, nomor HP akun, jenis kelamin) lalu mengaitkan santri tsb.
     * Read-only.
     *
     * @param  array<int, int>  $studentIds
     */
    public function previewNew(?int $employeeId, array $studentIds): array
    {
        $state = $this->inspectNew($employeeId, $studentIds);

        return [
            'can_link' => empty($state->blockers),
            'blockers' => $state->blockers,
            'warnings' => [],
            'new_parent' => true,
            'employee' => $state->employee ? [
                'id' => $state->employee->id, 'name' => $state->employee->name,
                'nip' => $state->employee->nip, 'phone' => $state->employee->phone,
            ] : null,
            'parent' => [
                'id' => null,
                'name' => $state->employee?->name,
                'phone' => $state->employee_user?->phone,
            ],
            'old_user' => null,
            'children' => $state->students->map(fn ($st) => ['name' => $st->name, 'nis' => $st->nis, 'class' => $st->class?->name ?? '-'])->values()->all(),
            'moved' => [],
            'moved_rows' => 0,
            'new_parent_phone' => $state->employee_user?->phone,
        ];
    }

    /**
     * Buat data orang tua untuk akun pegawai dan kaitkan santri yang dipilih (semuanya belum punya orang tua).
     *
     * @param  array<int, int>  $studentIds
     * @return array{link_id: int, parent_id: int, moved_rows: int, old_user_id: null}
     *
     * @throws AccountLinkException
     */
    public function linkNew(int $employeeId, array $studentIds): array
    {
        return DB::transaction(function () use ($employeeId, $studentIds) {
            $employee = Employee::withoutGlobalScopes()->lockForUpdate()->find($employeeId);
            $employee && User::withoutGlobalScopes()->withTrashed()->where('id', $employee->id_user)->lockForUpdate()->get();
            Student::withoutGlobalScopes()->whereIn('id', $studentIds)->lockForUpdate()->get();

            $state = $this->inspectNew($employeeId, $studentIds);

            if (! empty($state->blockers)) {
                throw new AccountLinkException($state->blockers[0]['message'], $state->blockers);
            }

            $user = $state->employee_user;
            $ids = $state->students->pluck('id')->all();
            $previous = $state->students->mapWithKeys(fn ($st) => [$st->id => $st->id_parent])->all();

            $parentId = DB::table('parent')->insertGetId([
                'id_user' => $user->id,
                'name' => $employee->name,
                'phone' => $user->phone,
                'gender' => $employee->gender instanceof \BackedEnum ? $employee->gender->value : $employee->gender,
                'branch_id' => $user->branch_id,
                'created_by' => Auth::id() ?? 0,
            ]);

            DB::table('student')->whereIn('id', $ids)->update(['id_parent' => $parentId]);

            $logId = DB::table('account_link_log')->insertGetId([
                'action' => 'link',
                'employee_id' => $employee->id,
                'parent_id' => $parentId,
                'employee_user_id' => $user->id,
                'old_user_id' => null,
                'actor_id' => Auth::id(),
                'before' => json_encode([
                    'parent_id_user' => null, 'parent_phone' => null, 'old_user_deleted' => false,
                    'created_parent' => true, 'student_ids' => $ids, 'previous_parent' => $previous,
                ]),
                'moved' => json_encode([]),
            ]);

            $parent = Parents::withoutGlobalScopes()->find($parentId);
            $this->audit('link', $parent, $employee, $user, null, 0);

            return ['link_id' => $logId, 'parent_id' => $parentId, 'moved_rows' => 0, 'old_user_id' => null];
        });
    }

    /**
     * Putuskan parent dari akun pegawai dan kembalikan kondisi sebelum link() bila ada entri rollback.
     * Tanpa entri (misalnya dihubungkan manual lewat SQL), parent hanya dilepas dari akun pegawai.
     *
     * @return array{restored_user_id: int|null, moved_rows: int, had_log: bool, removed_parent: bool}
     *
     * @throws AccountLinkException
     */
    public function unlink(int $parentId): array
    {
        return DB::transaction(function () use ($parentId) {
            $parent = Parents::withoutGlobalScope(BranchScope::class)->lockForUpdate()->find($parentId);

            if (! $parent || ! $parent->id_user) {
                throw new AccountLinkException('Data orang tua tidak terhubung ke akun pegawai.');
            }

            $employee = Employee::withoutGlobalScopes()->withTrashed()->where('id_user', $parent->id_user)->first();

            if (! $employee) {
                throw new AccountLinkException('Akun orang tua ini bukan akun pegawai.');
            }

            $employeeUserId = $parent->id_user;
            $log = DB::table('account_link_log')
                ->where('parent_id', $parent->id)->where('action', 'link')
                ->where('employee_user_id', $employeeUserId)->whereNull('rolled_back_at')
                ->orderByDesc('id')->lockForUpdate()->first();

            $movedRows = 0;
            $restoredUserId = null;
            $removedParent = false;

            if ($log) {
                $before = json_decode($log->before, true);
                $moved = json_decode($log->moved, true) ?? [];

                // Kembalikan baris yang masih menunjuk ke user pegawai
                foreach ($moved as $key => $ids) {
                    [$table, $column] = explode('.', $key, 2);
                    $movedRows += DB::table($table)->whereIn('id', $ids)->where($column, $employeeUserId)
                        ->update([$column => $log->old_user_id]);
                }

                if ($log->old_user_id) {
                    $old = User::withoutGlobalScopes()->withTrashed()->lockForUpdate()->find($log->old_user_id);

                    if ($old && $old->trashed()) {
                        $old->restore();
                        DB::table('users')->where('id', $old->id)->update(['deleted_by' => null]);
                    }

                    $restoredUserId = $old?->id;
                }

                $parent->id_user = $before['parent_id_user'];
                $parent->phone = $before['parent_phone'];

                // Data orang tua dibuat otomatis dari santri tanpa orang tua: bila belum dipakai (saldo 0,
                // tidak ada santri lain), kembalikan santri ke kondisi tanpa orang tua dan hapus data itu
                if (! empty($before['created_parent'])) {
                    $recorded = $before['student_ids'] ?? [];
                    $studentCount = DB::table('student')->where('id_parent', $parent->id)->count();
                    $matching = DB::table('student')->where('id_parent', $parent->id)->whereIn('id', $recorded)->count();

                    if ((float) $parent->balance == 0.0 && $studentCount === $matching) {
                        foreach ($recorded as $studentId) {
                            DB::table('student')->where('id', $studentId)->where('id_parent', $parent->id)
                                ->update(['id_parent' => $before['previous_parent'][$studentId] ?? null]);
                        }
                        $parent->id_user = null;
                        $parent->saveQuietly();
                        DB::table('parent')->where('id', $parent->id)->update(['deleted_at' => now(), 'deleted_by' => Auth::id()]);
                        $removedParent = true;
                    }
                }
            } else {
                $parent->id_user = null;
            }

            if (! $removedParent) {
                $parent->saveQuietly();
            }

            if ($log) {
                DB::table('account_link_log')->where('id', $log->id)->update([
                    'rolled_back_at' => now(), 'rolled_back_by' => Auth::id(),
                ]);
            }

            DB::table('account_link_log')->insert([
                'action' => 'unlink',
                'employee_id' => $employee->id,
                'parent_id' => $parent->id,
                'employee_user_id' => $employeeUserId,
                'old_user_id' => $restoredUserId,
                'actor_id' => Auth::id(),
                'before' => json_encode(['parent_id_user' => $employeeUserId]),
                'moved' => json_encode([]),
                'link_id' => $log?->id,
            ]);

            $this->audit('unlink', $parent, $employee, User::withoutGlobalScopes()->find($employeeUserId), null, $movedRows);

            return ['restored_user_id' => $restoredUserId, 'moved_rows' => $movedRows, 'had_log' => $log !== null, 'removed_parent' => $removedParent];
        });
    }

    /**
     * Untuk tiap santri: id data orang tua yang aktif, atau null bila santri belum punya (id_parent kosong,
     * data orangtuanya tidak ada, atau sudah dihapus).
     *
     * @param  array<int, int>  $studentIds
     * @return array<int, int|null>
     */
    public function activeParentIds(array $studentIds): array
    {
        $students = Student::withoutGlobalScopes()->whereIn('id', $studentIds)->get(['id', 'id_parent']);
        $active = Parents::withoutGlobalScope(BranchScope::class)
            ->whereIn('id', $students->pluck('id_parent')->filter()->all())->pluck('id')->all();

        return $students->mapWithKeys(fn ($st) => [
            $st->id => ($st->id_parent && in_array($st->id_parent, $active, true)) ? (int) $st->id_parent : null,
        ])->all();
    }

    /**
     * Status penggabungan untuk satu orang tua: sudah terhubung ke pegawai mana (bila ada).
     */
    public function linkedEmployee(Parents $parent): ?Employee
    {
        if (! $parent->id_user) {
            return null;
        }

        return Employee::withoutGlobalScopes()->withTrashed()->where('id_user', $parent->id_user)->first();
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: array<string, string>}>
     */
    private function columns(): array
    {
        $columns = [];

        foreach (config('account-link.columns', []) as $table => $tableColumns) {
            foreach ($tableColumns as $column => $extra) {
                $columns[] = [$table, $column, $extra];
            }
        }

        return $columns;
    }

    private function referenceQuery(string $table, string $column, array $extra, int $userId)
    {
        $query = DB::table($table)->where($column, $userId);

        foreach ($extra as $extraColumn => $value) {
            $query->where($extraColumn, $value);
        }

        return $query;
    }

    /**
     * Kumpulkan data dan pemblokir. Read-only.
     */
    private function inspect(?int $employeeId, int $parentId, bool $forWrite): object
    {
        $blockers = [];
        $warnings = [];
        $block = function (string $code, string $message) use (&$blockers) {
            $blockers[] = ['code' => $code, 'message' => $message];
        };

        $employee = $employeeId ? Employee::withoutGlobalScopes()->withTrashed()->find($employeeId) : null;
        $parent = Parents::withoutGlobalScope(BranchScope::class)->withTrashed()->find($parentId);
        $employeeUser = $employee ? User::withoutGlobalScopes()->withTrashed()->find($employee->id_user) : null;
        $oldUser = null;

        if ($employeeId && ! $employee) {
            $block('employee_missing', 'Data pegawai tidak ditemukan.');
        }

        if (! $parent) {
            $block('parent_missing', 'Data orang tua tidak ditemukan.');
        }

        if ($employee && ($employee->trashed() || ! $employee->status)) {
            $block('employee_inactive', 'Pegawai nonaktif atau sudah dihapus.');
        }

        if ($employee && (! $employeeUser || $employeeUser->trashed())) {
            $block('employee_user_missing', 'Akun pegawai tidak ditemukan atau sudah dihapus.');
        }

        if ($parent && $parent->trashed()) {
            $block('parent_deleted', 'Data orang tua sudah dihapus.');
        }

        if ($parent && $parent->id_user) {
            $parentUser = User::withoutGlobalScopes()->withTrashed()->find($parent->id_user);

            if ($employeeUser && $parent->id_user === $employeeUser->id) {
                $block('already_linked', 'Orang tua ini sudah terhubung ke akun pegawai tersebut.');
            } elseif ($parentUser && ! $parentUser->trashed()) {
                if ($parentUser->role !== UserRole::OrangTua) {
                    $block('old_user_not_parent', 'Orang tua ini sudah terhubung ke akun staf lain ('.$parentUser->name.'); akun staf tidak boleh dinonaktifkan.');
                } else {
                    $oldUser = $parentUser;
                }
            }
        }

        if ($employeeUser) {
            $other = Parents::withoutGlobalScope(BranchScope::class)->withTrashed()
                ->where('id_user', $employeeUser->id)->where('id', '!=', $parentId)->first();

            if ($other) {
                $block('user_has_other_parent', 'Akun pegawai ini sudah terhubung ke data orang tua lain ('.$other->name.').');
            }

            if ($employeeUser->phone && Parents::withoutGlobalScope(BranchScope::class)
                ->where('phone', $employeeUser->phone)->where('id', '!=', $parentId)->exists()) {
                $block('phone_used_by_other_parent', 'Nomor HP pegawai sudah dipakai data orang tua lain.');
            }
        }

        // BranchScope menyaring Parent/Student/Transaction berdasarkan users.branch_id. Cabang pegawai
        // yang berbeda membuat data anak tidak terlihat setelah penggabungan.
        if ($employeeUser && $parent) {
            $branchMismatch = $parent->branch_id != $employeeUser->branch_id
                || Student::withoutGlobalScope(BranchScope::class)
                    ->where('id_parent', $parent->id)->where('branch_id', '!=', $employeeUser->branch_id)->exists();

            if ($branchMismatch) {
                $block('branch_mismatch', 'Cabang pegawai berbeda dengan cabang orang tua/santri; data anak tidak akan terlihat.');
            }
        }

        if ($parent && ! Student::withoutGlobalScope(BranchScope::class)->where('id_parent', $parent->id)->exists()) {
            $warnings[] = 'Orang tua ini belum punya santri; menu orang tua baru tampil setelah ada santri.';
        }

        if ($employeeUser && $parent && $parent->phone && $employeeUser->phone && $parent->phone !== $employeeUser->phone) {
            $warnings[] = 'Nomor HP orang tua ('.$parent->phone.') akan disamakan dengan nomor akun pegawai ('.$employeeUser->phone.').';
        }

        return (object) [
            'employee' => $employee,
            'parent' => $parent,
            'employee_user' => $employeeUser,
            'old_user' => $oldUser,
            'blockers' => $blockers,
            'warnings' => $warnings,
        ];
    }

    /**
     * Pemblokir untuk membuat data orang tua baru dari akun pegawai. Read-only.
     *
     * @param  array<int, int>  $studentIds
     */
    private function inspectNew(?int $employeeId, array $studentIds): object
    {
        $blockers = [];
        $block = function (string $code, string $message) use (&$blockers) {
            $blockers[] = ['code' => $code, 'message' => $message];
        };

        $employee = $employeeId ? Employee::withoutGlobalScopes()->withTrashed()->find($employeeId) : null;
        $employeeUser = $employee ? User::withoutGlobalScopes()->withTrashed()->find($employee->id_user) : null;
        $students = Student::withoutGlobalScope(BranchScope::class)->with('class:id,name')
            ->whereIn('id', $studentIds)->orderBy('name')->get();

        if ($employeeId && ! $employee) {
            $block('employee_missing', 'Data pegawai tidak ditemukan.');
        }

        if ($employee && ($employee->trashed() || ! $employee->status)) {
            $block('employee_inactive', 'Pegawai nonaktif atau sudah dihapus.');
        }

        if ($employee && (! $employeeUser || $employeeUser->trashed())) {
            $block('employee_user_missing', 'Akun pegawai tidak ditemukan atau sudah dihapus.');
        }

        if ($students->isEmpty() || $students->count() !== count(array_unique($studentIds))) {
            $block('students_missing', 'Santri yang dipilih tidak ditemukan.');
        }

        if (collect($this->activeParentIds($students->pluck('id')->all()))->contains(fn ($id) => $id !== null)) {
            $block('students_have_parent', 'Salah satu santri yang dipilih sudah punya data orang tua.');
        }

        if ($employeeUser) {
            $other = Parents::withoutGlobalScope(BranchScope::class)->withTrashed()->where('id_user', $employeeUser->id)->first();

            if ($other) {
                $block('user_has_other_parent', 'Akun pegawai ini sudah terhubung ke data orang tua ('.$other->name.').');
            }

            if ($employeeUser->phone && Parents::withoutGlobalScope(BranchScope::class)->where('phone', $employeeUser->phone)->exists()) {
                $block('phone_used_by_other_parent', 'Nomor HP pegawai sudah dipakai data orang tua lain.');
            }

            if ($students->contains(fn ($st) => $st->branch_id != $employeeUser->branch_id)) {
                $block('branch_mismatch', 'Cabang pegawai berbeda dengan cabang santri; data anak tidak akan terlihat.');
            }
        }

        return (object) [
            'employee' => $employee,
            'employee_user' => $employeeUser,
            'students' => $students,
            'blockers' => $blockers,
        ];
    }

    private function audit(string $event, Parents $parent, Employee $employee, ?User $employeeUser, ?User $oldUser, int $movedRows): void
    {
        activity('account-link')
            ->causedBy(Auth::user())
            ->performedOn($parent)
            ->withProperties([
                'event' => $event,
                'employee_id' => $employee->id,
                'parent_id' => $parent->id,
                'employee_user_id' => $employeeUser?->id,
                'old_user_id' => $oldUser?->id,
                'moved_rows' => $movedRows,
            ])
            ->log("Account {$event}");
    }
}
