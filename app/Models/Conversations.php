<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversations extends Model
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
        'updated_at',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Modules::class, 'module_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agents::class, 'agent_id');
    }

    public function collection()
    {
        return $this->belongsTo(Collections::class, 'collection_id');
    }

    public function messages()
    {
        return $this->hasMany(Messages::class, 'conversation_id');
    }
}
