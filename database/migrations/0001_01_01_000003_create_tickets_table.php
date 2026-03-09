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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('title')->nullable();

            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('description');

            $table->enum('status', [
                'open',
                'assigned',
                'in_progress',
                'resolved',
                'closed'
            ])->default('open');

            $table->enum('priority', [
                'low',
                'normal',
                'high',
                'critical'
            ])->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

    }
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
