<?php

namespace App\Console\Commands;

use App\Services\FineService;
use Illuminate\Console\Command;

class CheckPenalties extends Command
{
    protected $signature = 'penalties:check';
    protected $description = 'Check and apply penalties for overdue borrowings';

    public function handle()
    {
        $this->info('Checking penalties for unpaid fines...');

        $fineService = app(FineService::class);
        $penaltiesApplied = $fineService->applyPendingPenalties();

        $this->info("Penalty check completed. Applied {$penaltiesApplied} penalties.");
        
        return 0;
    }
}
