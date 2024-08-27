<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Storm\Chronicler\Database\EventStreamDatabaseProvider;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            EventStreamDatabaseProvider::TABLE_NAME,
            static function (Blueprint $table): void {
                $table->bigInteger('id', true);
                $table->string('real_stream_name', 150)->unique();
                $table->string('stream_name', 150);
                $table->string('partition', 150)->nullable();

                $table->index('partition');
            });
    }

    public function down(): void
    {
        Schema::dropIfExists(EventStreamDatabaseProvider::TABLE_NAME);
    }
};
