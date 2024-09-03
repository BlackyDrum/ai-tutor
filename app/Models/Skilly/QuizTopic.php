<?php

namespace App\Models\Skilly;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizTopic extends Model
{
    use HasFactory;

    protected $table = 'skilly_quiz_topics';

    protected $fillable = [
        'name',
        'module_id',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
