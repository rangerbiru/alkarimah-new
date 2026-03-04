<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('temp:clear')->dailyAt('00:30');
Schedule::command('bill:expired')->everyTwoHours();
Schedule::command('payroll:generate')->monthlyOn(1, '01:00');
