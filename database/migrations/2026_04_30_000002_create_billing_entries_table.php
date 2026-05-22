<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->date('month'); // stored as YYYY-MM-01
            $table->decimal('amount_billed', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_entries');
    }
};
