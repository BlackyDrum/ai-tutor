<?php

namespace App\Models\Skilly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $table = 'skilly_quiz_questions';

    protected $fillable = [
        'question',
        'correct_answer',
        'wrong_answer_a',
        'wrong_answer_b',
        'wrong_answer_c',
        'description',
        'prompt_tokens',
        'completion_tokens',
        'openai_language_model'
    ];
}
