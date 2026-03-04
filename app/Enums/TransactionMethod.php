<?php

namespace App\Enums;

enum TransactionMethod: string
{
    case Cash = '1';
    case BNI = '2';
    case BSI = '3';
    case TopupBalance = '4';
}
