<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Console\Command;

class PayrollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate payroll for this month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = date('n');
        $year = date('Y');
        $employee = Employee::select('id', 'salary', 'salary_allowance', 'salary_allowance_detail', 'branch_id')
            ->where('salary', '>', 0)
            ->active()
            ->get();

        foreach ($employee as $e) {
            $total = $e->salary + $e->salary_allowance;

            Payroll::create([
                'id_employee' => $e->id,
                'months' => $month,
                'years' => $year,
                'salary' => $e->salary,
                'allowance' => $e->salary_allowance,
                'allowance_detail' => $e->salary_allowance_detail,
                'total' => $total,
                'branch_id' => $e->branch_id
            ]);
        }
    }
}
