<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversations extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'creating_user',
        'max_tokens',
        'temperature',
        'api_id',
        'user_id',
    ];

    protected $hidden = [
        'id',
        'agent_id',
        'creating_user',
        'max_tokens',
        'temperature',
        'user_id',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'api_id' => 'string',
    ];

    public function agent()
    {
        return $this->belongsTo(Agents::class, 'agent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(Messages::class, 'conversation_id');
    }
}
