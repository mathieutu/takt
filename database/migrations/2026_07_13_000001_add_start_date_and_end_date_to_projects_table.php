<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('deleted_at', 'end_date');
            $table->date('start_date')->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->date('end_date')->nullable()->change();
        });

        DB::table('projects')->update(['start_date' => DB::raw('date(created_at)')]);

        Schema::table('projects', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->timestamp('end_date')->nullable()->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('end_date', 'deleted_at');
        });
    }
};
