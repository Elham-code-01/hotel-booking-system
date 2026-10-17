<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('hotel_id')->nullable()->constrained()->nullOnDelete();
            $table->date('charged_on');
            $table->string('description');
            $table->string('charge_type', 10)->default('nightly');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('nights')->default(1);
            $table->decimal('price_sar', 10, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'charged_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_charges');
    }
};
