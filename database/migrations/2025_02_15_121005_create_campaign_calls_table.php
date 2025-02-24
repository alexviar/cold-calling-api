<?php

use App\Models\Campaign;
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
        Schema::create('campaign_calls', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            // Estado: pending, done, failed
            $table->tinyInteger('status')->unsigned()->default(1);
            $table->boolean('is_test')->default(false);
            $table->datetime('called_at')->nullable();
            $table->integer('duration')->unsigned()->nullable();
            $table->decimal('cost', 19, 6)->unsigned()->nullable();
            $table->json('telnyx_data')->nullable();
            $table->json('transcript')->nullable();
            $table->string('recording_path')->nullable();
            $table->string('interest_level')->nullable();
            $table->foreignIdFor(Campaign::class)->constrained();
            $table->datetimes();

            $table->unique(['campaign_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_calls');
    }
};
