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
        Schema::create('fine_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fine_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('paid_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('processed_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('cash')->comment('cash, transfer');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fine_payments');
    }
};
