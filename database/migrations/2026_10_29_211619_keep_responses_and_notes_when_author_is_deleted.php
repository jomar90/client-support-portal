<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Responses and notes are kept when their author is deleted.
     * Their user_id becomes null instead of the whole row being deleted.
     */
    public function up(): void
    {
        foreach (['responses', 'notes'] as $tableName) {
            // MySQL cannot change a column while a foreign key uses it,
            // so the old cascading foreign key is removed first.
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * A rollback is refused while any response or note has no author,
     * because user_id cannot become required again without deleting those rows.
     */
    public function down(): void
    {
        foreach (['responses', 'notes'] as $tableName) {
            if (DB::table($tableName)->whereNull('user_id')->exists()) {
                throw new RuntimeException(
                    "Cannot roll back: some {$tableName} belong to a deleted user (user_id is null)."
                );
            }
        }

        foreach (['responses', 'notes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }
};
