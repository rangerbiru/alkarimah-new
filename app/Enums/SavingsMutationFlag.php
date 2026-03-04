<?php

namespace App\Enums;

enum SavingsMutationFlag: string
{
    case Deposit = '1';
    case Withdrawal = '2';
}
