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
        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('member_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('type')->comment('late_return, lost_book, damage');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status')->default('unpaid')->comment('unpaid, partial, paid');
            $table->date('due_date')->comment('Fine due date');
            $table->date('paid_at')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
