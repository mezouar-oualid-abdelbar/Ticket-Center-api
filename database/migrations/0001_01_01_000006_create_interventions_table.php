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
         Schema::create('interventions', function (Blueprint $table) {
            $table->id();

            // When the technician plans to visit/work
            $table->timestamp('appointment');

            // Related ticket
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete(); 

            // Notes about the intervention
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Helpful indexes
            $table->index('ticket_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
