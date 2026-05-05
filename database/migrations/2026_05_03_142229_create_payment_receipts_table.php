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
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_verification_id')->constrained()->onDelete('cascade');
            $table->string('receipt_number', 50)->unique();
            $table->json('receipt_data');
            $table->string('qr_code', 255);
            $table->string('pdf_path', 255)->nullable();
            $table->enum('sent_via', ['email', 'sms', 'download'])->default('download');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->index(['payment_verification_id']);
            $table->index(['receipt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
