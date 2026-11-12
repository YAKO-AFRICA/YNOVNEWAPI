<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql')->create('branches', function (Blueprint $table) {
            $table->uuid('uuid_branche')->primary();
            $table->unsignedBigInteger('IdCanalDistribution');
            $table->string('CodeCanalDistribution', 100)->unique();
            $table->string('MonLibelle');
            $table->string('etat', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('branches');
    }
};
