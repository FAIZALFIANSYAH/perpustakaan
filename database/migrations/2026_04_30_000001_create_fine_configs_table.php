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
        Schema::create('fine_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('grace_period_days')->default(0)->comment('Grace period after due date before fine starts');
            $table->decimal('fine_per_day', 10, 2)->default(1000)->comment('Fine amount per day in Rupiah');
            $table->decimal('lost_book_fine', 10, 2)->default(50000)->comment('Fine amount for lost book in Rupiah');
            $table->integer('max_fine_cap')->nullable()->comment('Maximum fine cap in Rupiah (null for no cap)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fine_configs');
    }
};
