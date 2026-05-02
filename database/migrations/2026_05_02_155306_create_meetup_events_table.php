<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetup_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meetup_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->json('attendees')->nullable();
            $table->json('might_attendees')->nullable();
            $table->string('nostr_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetup_events');
    }
};
