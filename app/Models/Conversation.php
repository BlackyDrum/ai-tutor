<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'url_id',
        'openai_language_model',
        'prompt_tokens',
        'completion_tokens',
        'name_edited',
        'agent_id',
        'user_id',
        'module_id',
        'collection_id',
    ];

    protected $hidden = [
        'id',
        'openai_language_model',
        'prompt_tokens',
        'completion_tokens',
        'name_edited',
        'agent_id',
        'user_id',
        'module_id',
        'collection_id',
        'updated_at',
        'created_at',
        'deleted_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
