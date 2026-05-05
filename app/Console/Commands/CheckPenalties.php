<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Models\Fine;
use App\Services\BorrowingService;
use App\Services\FineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPenalties extends Command
{
    protected $signature = 'penalties:check';
    protected $description = 'Check and apply penalties for overdue borrowings';

    public function handle()
    {
        $this->info('Checking penalties for overdue borrowings...');

        $overdueBorrowings = Borrowing::where('status', 'overdue')
            ->where('due_at', '<', now())
            ->with(['items.fines', 'member'])
            ->get();

        $penaltiesApplied = 0;
        $borrowingService = app(BorrowingService::class);
        $fineService = app(FineService::class);

        foreach ($overdueBorrowings as $borrowing) {
            $daysOverdue = $borrowing->due_at->diffInDays(now(), false);
            
            if ($fineService->shouldApplyPenalty($daysOverdue)) {
                $hasPenalty = Fine::whereHas('borrowingItem', function ($query) use ($borrowing) {
                    $query->where('borrowing_id', $borrowing->id);
                })->where('type', 'penalty')
                  ->where('status', 'unpaid')
                  ->exists();

                if (!$hasPenalty) {
                    $borrowingService->checkAndApplyPenalty($borrowing);
                    $penaltiesApplied++;
                    
                    $this->info("Penalty applied for borrowing #{$borrowing->id} ({$borrowing->member->name})");
                    
                    Log::info("Penalty applied", [
                        'borrowing_id' => $borrowing->id,
                        'member_id' => $borrowing->member_id,
                        'member_name' => $borrowing->member->name,
                        'days_overdue' => $daysOverdue,
                    ]);
                }
            }
        }

        $this->info("Penalty check completed. Applied {$penaltiesApplied} penalties.");
        
        return 0;
    }
}