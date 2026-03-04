<?php

namespace App\Enums;

enum SavingsWithdrawalStatus: string
{
    case Waiting = '0';
    case Processed = '1';
}
