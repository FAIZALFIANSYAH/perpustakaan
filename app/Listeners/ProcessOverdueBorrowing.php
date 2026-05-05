<?php

namespace App\Listeners;

use App\Events\BorrowingCreated;
use App\Services\BorrowingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessOverdueBorrowing implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected BorrowingService $borrowingService
    ) {}

    public function handle(BorrowingCreated $event): void
    {
        $borrowing = $event->borrowing;
        
        // Check if borrowing is already overdue
        if ($borrowing->due_at < now()) {
            $this->borrowingService->checkAndUpdateOverdueStatus();
        }
    }
}