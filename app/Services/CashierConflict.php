<?php

namespace App\Services;

use App\Exceptions\CashierConflictException;
use App\Helpers\Phone;
use App\Models\Parents;
use App\Models\Scopes\BranchScope;
use App\Models\Student;
use App\Models\SavingsWithdrawal;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Konflik kepentingan kasir: siapa pun yang menjalankan aksi kasir (kasir, kasir unit, admin, pegawai
 * pemegang modul, ...) tidak boleh memproses tagihan / pembayaran / tabungan anaknya sendiri.
 *
 * "Anak sendiri" = santri milik baris orang tua si pelaku. Baris orang tua dicari lewat relasi
 * (parent.id_user = user.id) lalu, sebagai cadangan, lewat 10 digit terakhir nomor HP (users.phone
 * = parent.phone; dilewati bila HP kosong / kurang dari 10 digit).
 *
 * Penolakan dilakukan di server dengan CashierConflictException (403, dicatat ke activity_log).
 */
class CashierConflict
{
    /** @return array<int, int> */
    public static function ownParentIds(User $user): array
    {
        $ids = Parents::withoutGlobalScope(BranchScope::class)->withTrashed()
            ->where('id_user', $user->id)->pluck('id')->all();

        $lastTen = Phone::lastTen($user->phone);

        if ($lastTen) {
            $byPhone = Parents::withoutGlobalScope(BranchScope::class)
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', ''), 10) = ?", [$lastTen])
                ->pluck('id')->all();

            $ids = array_merge($ids, $byPhone);
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /** @return array<int, int> */
    public static function ownStudentIds(User $user): array
    {
        $parents = self::ownParentIds($user);

        if (! $parents) {
            return [];
        }

        return Student::withoutGlobalScopes()->withTrashed()->whereIn('id_parent', $parents)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return array<int, int> id santri dari $studentIds yang adalah anak $user */
    public static function conflictingStudents(User $user, iterable $studentIds): array
    {
        $ids = self::ids($studentIds);

        return $ids ? array_values(array_intersect($ids, self::ownStudentIds($user))) : [];
    }

    /** @return array<int, int> id orang tua dari $parentIds yang adalah baris orang tua $user */
    public static function conflictingParents(User $user, iterable $parentIds): array
    {
        $ids = self::ids($parentIds);

        return $ids ? array_values(array_intersect($ids, self::ownParentIds($user))) : [];
    }

    /** Peringatan di form (tidak menolak): apakah santri ini anak $user? */
    public static function isOwnStudent(User $user, ?int $studentId): bool
    {
        return $studentId && self::conflictingStudents($user, [$studentId]) !== [];
    }

    /**
     * Lempar CashierConflictException bila ada santri / orang tua yang terkait adalah milik $user.
     *
     * @throws CashierConflictException
     */
    public static function assert(User $user, string $action, iterable $studentIds = [], iterable $parentIds = []): void
    {
        $students = self::conflictingStudents($user, $studentIds);
        $parents = self::conflictingParents($user, $parentIds);

        // Santri dari orang tua yang diproses (mis. saldo orang tua) juga dihitung
        if ($parents === [] && $students === []) {
            return;
        }

        throw new CashierConflictException($user->id, $action, $students, $parents);
    }

    /**
     * Santri dan orang tua yang terlibat pada transaksi (tagihan: santri + santri tiap tagihan;
     * setor tabungan: santri; topup / pengambilan saldo: orang tua).
     *
     * @param  iterable<int>  $transactionIds
     * @return array{0: array<int, int>, 1: array<int, int>} [studentIds, parentIds]
     */
    public static function partiesOfTransactions(iterable $transactionIds): array
    {
        $ids = self::ids($transactionIds);

        if (! $ids) {
            return [[], []];
        }

        $transactions = Transaction::withoutGlobalScopes()->withTrashed()->whereIn('id', $ids)->get(['id', 'id_student', 'id_parent', 'bills']);
        $students = $transactions->pluck('id_student')->filter()->all();
        $parents = $transactions->pluck('id_parent')->filter()->all();

        $billIds = $transactions->flatMap(fn ($t) => is_array($t->bills) ? $t->bills : [])->all();

        if ($billIds) {
            $students = array_merge($students, self::studentsOfBills($billIds));
        }

        return [self::ids($students), self::ids($parents)];
    }

    /** @return array<int, int> */
    public static function studentsOfBills(iterable $transactionBillIds): array
    {
        $ids = self::ids($transactionBillIds);

        return $ids
            ? self::ids(TransactionBill::withoutGlobalScopes()->whereIn('id', $ids)->pluck('id_student')->all())
            : [];
    }

    /** @return array<int, int> */
    public static function studentsOfWithdrawals(iterable $withdrawalIds): array
    {
        $ids = self::ids($withdrawalIds);

        return $ids
            ? self::ids(SavingsWithdrawal::withoutGlobalScopes()->withTrashed()->whereIn('id', $ids)->pluck('id_student')->all())
            : [];
    }

    /** @return array<int, int> */
    private static function ids(iterable $values): array
    {
        $out = [];

        foreach ($values as $value) {
            if ($value !== null && $value !== '' && is_numeric($value)) {
                $out[] = (int) $value;
            }
        }

        return array_values(array_unique($out));
    }
}
