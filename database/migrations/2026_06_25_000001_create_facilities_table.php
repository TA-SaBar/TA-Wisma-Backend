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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['kamar', 'ruang_rapat']);
            $table->string('gedung');
            $table->string('lantai');
            $table->integer('capacity');
            $table->decimal('price', 15, 2);
            $table->string('unit')->default('night'); // night, 4_jam, day
            $table->string('luas')->nullable();
            $table->string('bed')->nullable();
            $table->enum('status', ['READY', 'OCCUPIED', 'CLEANING', 'MAINTENANCE'])->default('READY');
            $table->string('photo')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
