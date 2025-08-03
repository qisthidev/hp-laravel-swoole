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
        Schema::create('postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('region_id');
            $table->string('area_name');
            $table->point('coordinates')->nullable();
            $table->string('delivery_office')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('region_id');
            $table->index('area_name');
            $table->spatialIndex('coordinates');

            // Foreign key
            $table->foreign('region_id')->references('id')->on('administrative_regions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postal_codes');
    }
};