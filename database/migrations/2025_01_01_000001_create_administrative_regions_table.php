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
        Schema::create('administrative_regions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['provinsi', 'kabupaten', 'kota', 'kecamatan', 'kelurahan', 'desa']);
            $table->string('code')->unique();
            $table->string('parent_id')->nullable();
            $table->json('postal_codes')->nullable();
            $table->point('coordinates')->nullable();
            $table->geometry('boundaries')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->bigInteger('population')->nullable();
            $table->text('description')->nullable();
            $table->string('dataset_url')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('parent_id');
            $table->index('code');
            $table->index(['name', 'type']);
            $table->spatialIndex('coordinates');
            $table->spatialIndex('boundaries');

            // Foreign key
            $table->foreign('parent_id')->references('id')->on('administrative_regions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrative_regions');
    }
};