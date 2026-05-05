<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('penalty_enabled')->default(true);
            $table->integer('grace_period_penalty_days')->default(3)->comment('Grace period before penalty applies');
            $table->decimal('penalty_multiplier', 8, 2)->default(2.00)->comment('Multiplier for penalty calculation');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_configs');
    }
};