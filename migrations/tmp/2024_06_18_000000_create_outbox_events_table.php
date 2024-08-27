<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'outbox_events',
            static function (Blueprint $table): void {
                $table->id();
                $table->uuid('event_id')->unique();
                $table->text('payload');
                $table->timestamp('created_at', 6);
                $table->timestamp('processed_at', 6)->nullable();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
