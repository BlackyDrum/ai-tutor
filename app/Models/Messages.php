<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Messages extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_message',
        'agent_message',
        'user_message_with_context',
        'prompt_tokens',
        'completion_tokens',
        'openai_language_model',
        'conversation_id',
        'helpful',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'user_message_with_context',
        'prompt_tokens',
        'completion_tokens',
        'openai_language_model',
        'conversation_id',
        'created_at',
        'updated_at',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversations::class, 'conversation_id');
    }

    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Hashids::encode($value)
        );
    }
}
