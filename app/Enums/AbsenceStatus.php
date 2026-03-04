<?php

namespace App\Enums;

enum AbsenceStatus: string
{
    case Absen = '0';
    case Hadir = '1';
    case Izin = '2';
    case Sakit = '3';
}
