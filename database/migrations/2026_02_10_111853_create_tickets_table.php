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

    $table->date('done_at')->nullable();

    $table->text('description');

    $table->foreignId('reporter_id')
          ->constrained('users')
          ->cascadeOnDelete();

    $table->timestamps();
    $table->softDeletes();
});

    }
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
