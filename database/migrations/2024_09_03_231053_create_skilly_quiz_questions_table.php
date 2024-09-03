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
        Schema::create('skilly_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('correct_answer');
            $table->string('wrong_answer_a');
            $table->string('wrong_answer_b');
            $table->string('wrong_answer_c');
            $table->text('description');
            $table->unsignedInteger('prompt_tokens');
            $table->unsignedInteger('completion_tokens');
            $table->string('openai_language_model');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skilly_quiz_questions');
    }
};
