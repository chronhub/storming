<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_event', function (Blueprint $table) {
            $table->bigIncrements('position');
            $table->string('stream_name');
            $table->string('type');
            $table->uuid('id');
            $table->bigInteger('version');
            $table->jsonb('header');
            $table->jsonb('content');
            $table->timestampTz('created_at', 6)->useCurrent();

            $table->primary(['position', 'stream_name'], 'pk_stream_event');
            $table->unique(['type', 'id', 'version'], 'uk_stream_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_event');
    }
};
