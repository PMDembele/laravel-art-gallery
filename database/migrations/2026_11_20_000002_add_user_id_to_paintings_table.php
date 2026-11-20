<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Связь картины с пользователем, который её добавил (paintings.user_id -> users.id)
    public function up(): void
    {
        Schema::table('paintings', function (Blueprint $table) {
            // nullable: у картин, добавленных до ЛР4, владельца ещё нет — им назначит сидер
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('paintings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
