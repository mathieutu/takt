<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->restrictOnDelete()->cascadeOnUpdate();
            $table->date('date')->index();
            $table->unsignedInteger('coverage')->default(0);
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'date']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_entries');
    }
};
