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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('user_message');
            $table->text('agent_message');
            $table->text('user_message_with_context');
            $table->unsignedInteger('prompt_tokens');
            $table->unsignedInteger('completion_tokens');
            $table->string('openai_language_model');
            $table->unsignedBigInteger('conversation_id');
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
