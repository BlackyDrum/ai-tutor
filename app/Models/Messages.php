<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_message',
        'agent_message',
        'prompt_tokens',
        'completion_tokens',
        'conversation_id',
    ];

    protected $hidden = [
        'id',
        'conversation_id',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversations::class, 'conversation_id');
    }
}
