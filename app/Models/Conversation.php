<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'url_id',
        'user_id',
        'module_id',
        'name',
        'collection_id',
        'prompt_tokens',
        'completion_tokens',
        'openai_language_model',
        'name_edited',
    ];

    protected $hidden = [
        'id',
        'module_id',
        'agent_id',
        'temperature',
        'user_id',
        'collection_id',
        'prompt_tokens',
        'completion_tokens',
        'openai_language_model',
        'name_edited',
        'updated_at',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }
}
