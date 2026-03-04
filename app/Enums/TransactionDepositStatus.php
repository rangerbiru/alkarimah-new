<?php

namespace App\Enums;

enum TransactionDepositStatus: string
{
    case NotDeposit = '0';
    case Deposited = '1';
}
