<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversations extends Model
{
    use HasFactory;

    protected $fillable = [
        'openai_language_model',
        'url_id',
        'user_id',
        'module_id',
        'name',
    ];

    protected $hidden = [
        'id',
        'temperature',
        'user_id',
        'updated_at',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Messages::class, 'conversation_id');
    }
}
