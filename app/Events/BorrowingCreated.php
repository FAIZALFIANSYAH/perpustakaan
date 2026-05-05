<?php

namespace App\Events;

use App\Models\Borrowing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BorrowingCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Borrowing $borrowing
    ) {}
}