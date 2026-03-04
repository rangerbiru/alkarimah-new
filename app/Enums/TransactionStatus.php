<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case NotPaid = '0';
    case Paid = '1';
    case Expired = '2';
}
