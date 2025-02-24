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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->tinyInteger('status')->unsigned()->default(1);

            $table->text('prompt');
            $table->text('greeting');
            $table->string('file_path')->nullable();
            $table->string('greeting_audio_path')->nullable();

            $table->$table->datetime('closed_at')->nullable();
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
