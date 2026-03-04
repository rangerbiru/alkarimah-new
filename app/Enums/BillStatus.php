<?php

namespace App\Enums;

enum BillStatus: string
{
    case NotPaid = '0';
    case Paid = '1';
}
