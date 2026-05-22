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
        Schema::create('notifications', function (Blueprint $table) {
             $table->id();

            // User receiving the notification
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Notification content
            $table->string('title');
            $table->text('message');

            // Example:
            // ticket_created
            // ticket_assigned
            // ticket_resolved
            $table->string('type')->nullable();

            // Read status
            $table->boolean('is_read')->default(false);

            // Optional relation to another model
            // Example:
            // related_id = 15
            // related_type = App\Models\Ticket
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
