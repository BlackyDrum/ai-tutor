<?php

namespace App\Models\Skilly;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

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
    ];

    protected function id(): Attribute
    {
        // We obfuscate the auto-incremented id here for the user
        return Attribute::make(
            get: fn(string $value) => Hashids::encode($value)
        );
    }
}
