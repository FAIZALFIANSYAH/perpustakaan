<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's update existing records to use new statuses
        \DB::statement("UPDATE borrowings SET status = 'borrowed' WHERE status = 'borrowed'");
        \DB::statement("UPDATE borrowings SET status = 'returned' WHERE status = 'returned'");
        \DB::statement("UPDATE borrowings SET status = 'returned' WHERE status = 'partial'");
        
        // No need to modify the table structure as we're using string status
        // The new statuses will be: 'borrowed', 'overdue', 'returned', 'late_payment', 'complete', 'lost'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old statuses
        \DB::statement("UPDATE borrowings SET status = 'borrowed' WHERE status IN ('overdue', 'late_payment', 'complete', 'lost')");
        \DB::statement("UPDATE borrowings SET status = 'partial' WHERE status = 'returned' AND returned_quantity < quantity");
    }
};
