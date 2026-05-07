<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Borrowing;
use App\Services\FineService;
use App\Services\BorrowingService;

class CheckOverdueBorrowings extends Command
{
    protected $signature = "borrowings:check-overdue";
    protected $description = "Check for overdue borrowings and generate fines";

    public function handle()
    {
        $this->info("Checking overdue borrowings...");
        
        $overdueBorrowings = Borrowing::where('due_at', '<', now())
            ->where('status', Borrowing::STATUS_BORROWED)
            ->with(['member', 'items.book', 'items.fines'])
            ->get();
        
        $fineService = app(FineService::class);
        $borrowingService = app(BorrowingService::class);
        
        foreach ($overdueBorrowings as $borrowing) {
            $this->info("Processing borrowing {$borrowing->id} for {$borrowing->member->name}");
            
            foreach ($borrowing->items as $item) {
                if ($item->fines->count() === 0) {
                    $fine = $fineService->createLateReturnFine($borrowing, $item, $item->quantity);
                    if ($fine) {
                        $this->info("  Generated fine {$fine->id} for " . $item->book->title);
                    }
                }
            }
            
            $borrowingService->checkAndUpdateOverdueStatus();
            $this->info("  Updated status to: " . $borrowing->fresh()->status);
        }
        
        $this->info("Overdue check completed!");
    }
}
