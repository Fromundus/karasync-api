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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("karaoke_id");
            $table->string("code");
            $table->string("thumbnail")->nullable();
            $table->string("title")->nullable();
            $table->string("channel")->nullable();
            $table->string("status");
            $table->string("color");
            $table->timestamps();

            $table->foreign("karaoke_id")->references("id")->on("karaokes")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
