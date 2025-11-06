<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_status_id')->constrained('job_statuses')->onDelete('cascade');
            $table->string('status', 16)->index();
            $table->text('status_message')->nullable();
            $table->unsignedInteger('progress_now')->default(0);
            $table->unsignedInteger('progress_max')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_status_histories');
    }
};
