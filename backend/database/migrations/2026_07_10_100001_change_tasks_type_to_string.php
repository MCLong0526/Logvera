<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Task types grew from a fixed enum to a broad list. A string column avoids
// re-ALTERing the enum every time the taxonomy changes.
return new class extends Migration
{
    // Old enum value => nearest value in the new taxonomy.
    private array $remap = [
        'bug_fix' => 'bug',
        'testing' => 'unit_testing',
        'meeting' => 'others',
        'documentation' => 'others',
        'research' => 'research_and_do',
    ];

    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('type')->default('development')->change();
        });

        foreach ($this->remap as $old => $new) {
            DB::table('tasks')->where('type', $old)->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        foreach (array_flip($this->remap) as $new => $old) {
            DB::table('tasks')->where('type', $new)->update(['type' => $old]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('type', ['development', 'bug_fix', 'testing', 'meeting', 'documentation', 'research', 'others'])
                ->default('development')->change();
        });
    }
};
