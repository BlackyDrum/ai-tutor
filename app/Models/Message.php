<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Message extends Model
{
    protected $fillable = [
        'user_message',
        'agent_message',
        'user_message_with_context',
        'openai_language_model',
        'prompt_tokens',
        'completion_tokens',
        'helpful',
        'conversation_id',
        'created_at',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    protected function id(): Attribute
    {
        // We obfuscate the auto-incremented id here for the user
        return Attribute::make(
            get: fn(string $value) => str_contains(
                request()->route()->getName(),
                'peek'
            )
                ? $value
                : Hashids::encode($value)
        );
    }
}
