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
        Schema::create('song_books', function (Blueprint $table) {
            $table->id();
            $table->string("code");
            $table->string("thumbnail")->nullable();
            $table->string("title")->nullable();
            $table->string("channel")->nullable();
            $table->string("status")->nullable();
            $table->string("color");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_books');
    }
};
