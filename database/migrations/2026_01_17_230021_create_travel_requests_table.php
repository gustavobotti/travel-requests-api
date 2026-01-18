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
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('requester_user_id');
            $table->string('requester_name');

            $table->string('destination');
            $table->date('departure_date');
            $table->date('return_date');

            $table->enum('status', ['REQUESTED', 'APPROVED', 'CANCELLED'])->default('REQUESTED');

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('requester_user_id');
            $table->index('status');
            $table->index('destination');
            $table->index('departure_date');
            $table->index('return_date');
            $table->index('created_at');
            $table->index(['departure_date', 'return_date']);

            // Foreign keys
            $table->foreign('requester_user_id')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('cancelled_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_requests');
    }
};
