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
        Schema::table('fines', function (Blueprint $table) {
            // Update status enum to include new payment verification statuses
            $table->dropColumn('status');
        });
        
        Schema::table('fines', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'pending_payment', 'partial', 'verified', 'paid'])->default('unpaid')->after('notes');
            $table->foreignId('payment_verification_id')->nullable()->after('status');
        });
        
        Schema::table('fines', function (Blueprint $table) {
            $table->foreign('payment_verification_id')->references('id')->on('payment_verifications')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->dropForeign(['payment_verification_id']);
            $table->dropColumn(['payment_verification_id']);
        });
        
        Schema::table('fines', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('fines', function (Blueprint $table) {
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('notes');
        });
    }
};
