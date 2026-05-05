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
        Schema::table('fine_configs', function (Blueprint $table) {
            $table->integer('max_borrowing_days')->default(7)->after('grace_period_days');
            $table->integer('max_billable_days')->default(5)->after('fine_per_day');
            $table->integer('max_fine_per_item')->default(10000)->after('lost_book_fine');
            $table->integer('max_fine_per_borrowing')->default(50000)->after('max_fine_per_item');
            $table->integer('lost_book_payment_deadline')->default(14)->after('max_fine_per_borrowing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fine_configs', function (Blueprint $table) {
            $table->dropColumn([
                'max_borrowing_days',
                'max_billable_days',
                'max_fine_per_item',
                'max_fine_per_borrowing',
                'lost_book_payment_deadline'
            ]);
        });
    }
};
