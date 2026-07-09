<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "Task It" kanban boards: board + members + cards in three fixed stages.
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['board_id', 'user_id']);
            $table->timestamps();
        });

        Schema::create('board_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('stage', ['assigned', 'in_progress', 'closed'])->default('assigned');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['board_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_cards');
        Schema::dropIfExists('board_members');
        Schema::dropIfExists('boards');
    }
};
