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
        Schema::create('security_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('method', 10);
            $table->string('url', 2000);
            $table->string('route_name')->nullable();
            $table->string('exception_class');
            $table->string('exception_message', 1000);
            $table->string('component_name')->nullable()->index();
            $table->string('target_property')->nullable();
            $table->json('payload')->nullable();
            $table->string('severity')->default('medium')->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_attempts');
    }
};
