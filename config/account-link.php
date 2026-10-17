<?php

/**
 * Kolom yang menyimpan id user dan boleh dipindahkan dari akun orang tua lama ke akun pegawai
 * saat penggabungan (App\Services\AccountLinker). Hanya kolom di daftar ini yang disentuh.
 *
 * Diverifikasi per kolom: merujuk ke users.id (diisi Auth::id() oleh model/controller) dan
 * benar-benar terisi oleh akun orang tua. Format: tabel => [kolom => syarat tambahan (opsional)].
 * Setiap tabel wajib punya primary key "id" (dipakai untuk rollback).
 *
 * Sengaja TIDAK ada: module_rights, user_rights, user_education_levels, class.id_wali_kelas
 * (hanya milik staf), transaction.paid_by / *.verified_by / *.processed_by (diisi kasir/bendahara
 * atau 0), student_*.student_id (merujuk student.id), users.updated_by dan kolom audit tabel lain.
 */
return [
    'columns' => [
        'transaction' => ['created_by' => [], 'updated_by' => [], 'deleted_by' => []],
        'transaction_bill' => ['created_by' => [], 'updated_by' => []],
        'transaction_payment_code' => ['created_by' => []],
        'activity_log' => ['causer_id' => ['causer_type' => 'App\\Models\\User']],
    ],
];
