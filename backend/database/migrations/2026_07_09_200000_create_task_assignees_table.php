<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tasks now support multiple assignees. Existing single assignees are
    // copied into the pivot before the old column is dropped — no data loss.
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['task_id', 'user_id']);
            $table->timestamps();
        });

        DB::statement('INSERT INTO task_assignees (task_id, user_id, created_at, updated_at)
            SELECT id, assigned_user_id, NOW(), NOW() FROM tasks WHERE assigned_user_id IS NOT NULL');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assigned_user_id')->nullable()->after('target_time')->constrained('users')->nullOnDelete();
        });

        // Keep the first assignee when collapsing back to a single column.
        DB::statement('UPDATE tasks t
            JOIN (SELECT task_id, MIN(user_id) AS user_id FROM task_assignees GROUP BY task_id) a ON a.task_id = t.id
            SET t.assigned_user_id = a.user_id');

        Schema::dropIfExists('task_assignees');
    }
};
