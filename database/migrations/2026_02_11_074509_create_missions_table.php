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
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tickets_id')
            ->constrained('tickets')
            ->cascadeOnDelete();
            $table->foreignId('assigned')
            ->constrained('crews')
            ->cascadeOnDelete();
            $table->foreignId('assigned_by')
            ->constrained('users');
            $table->date('schedule');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
