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
        Schema::create('geographic_boundaries', function (Blueprint $table) {
            $table->id();
            $table->string('region_id')->unique();
            $table->geometry('geometry')->nullable();
            $table->point('centroid')->nullable();
            $table->json('bbox')->nullable();
            $table->enum('precision', ['high', 'medium', 'low'])->default('medium');
            $table->string('source')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('region_id');
            $table->index('precision');
            $table->spatialIndex('geometry');
            $table->spatialIndex('centroid');

            // Foreign key
            $table->foreign('region_id')->references('id')->on('administrative_regions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geographic_boundaries');
    }
};