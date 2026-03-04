<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Waiting = '0';
    case Accepted = '1';
    case Rejected = '2';
}
