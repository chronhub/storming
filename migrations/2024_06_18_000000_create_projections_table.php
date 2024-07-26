<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Storm\Projector\Repository\ConnectionProjectionProvider;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create(
            ConnectionProjectionProvider::TABLE_NAME,
            static function (Blueprint $table): void {
                $table->bigInteger('no', true);
                $table->string('name', 150)->unique();
                $table->json('position');
                $table->json('state');
                $table->string('status', 28);
                $table->char('locked_until', 26)->nullable();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists(ConnectionProjectionProvider::TABLE_NAME);
    }
};
