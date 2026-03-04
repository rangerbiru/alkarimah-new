<?php

namespace App\Enums;

enum PaymentCodeStatus: string
{
    case NotUsed = '0';
    case Used = '1';
}
