<?php

namespace App\Services\Cancellation;

interface TransactionReverser
{
    /** Hanya membaca dan memeriksa; mengisi $ctx->plan dan blockers. Tidak menulis apa pun. */
    public function plan(Context $ctx): void;

    /** Membalik data jenis transaksi ini. $step dipanggil setelah tiap tahap (nama tahap sebagai argumen). */
    public function apply(Context $ctx, callable $step): void;

    /** Rincian tambahan untuk pratinjau. */
    public function describe(Context $ctx): array;

    /** True bila pembalikan bergantung pada kelas santri saat pembayaran. */
    public function dependsOnClass(): bool;
}
