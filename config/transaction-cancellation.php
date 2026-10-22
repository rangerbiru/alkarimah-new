<?php

use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;

return [
    // Pembayaran masuk lunas yang dibuat orang tua; tunai (kasir) dan pengambilan tabungan tidak termasuk
    'scope' => [
        TransactionFlag::Tagihan->value => [TransactionMethod::BNI->value, TransactionMethod::BSI->value, TransactionMethod::TopupBalance->value],
        TransactionFlag::SetorTabungan->value => [TransactionMethod::BNI->value, TransactionMethod::BSI->value],
        TransactionFlag::TopupSaldo->value => [TransactionMethod::BNI->value, TransactionMethod::BSI->value],
    ],
    'rules' => [
        'cash_deposit' => true, // selalu aktif (keputusan klien)
        'month_open' => false, // mati: transaksi bulan lalu hanya mendapat warnings[]
        'class_unchanged' => true,
    ],
    'reason' => ['min_words' => 3, 'min_chars' => 10],
];
