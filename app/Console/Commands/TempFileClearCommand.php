<?php

namespace App\Console\Commands;

use App\Models\TempFile;
use Illuminate\Console\Command;

class TempFileClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired temporary file (> 1 day)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $temp = TempFile::where('created_at', '<', date('Y-m-d H:i:s', strtotime('-1 day')))->get();

        foreach ($temp as $t)
            $t->delete();
    }
}
