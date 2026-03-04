<?php

namespace App\Enums;

enum TransactionFlag: string
{
    case Tagihan = '1';
    case SetorTabungan = '2';
    case PengambilanTabungan = '3';
    case TopupSaldo = '4';
}
