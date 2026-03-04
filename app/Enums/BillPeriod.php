<?php

namespace App\Enums;

enum BillPeriod: string
{
    case OneTime = '1';
    case Monthly = '2';
    case Semiannual = '3';
}
