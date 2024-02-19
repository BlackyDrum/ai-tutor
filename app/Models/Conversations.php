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
        'id',
        'user_id',
    ];

    protected $hidden = [
        'agent_id',
        'creating_user',
        'max_tokens',
        'temperature',
        'user_id',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'id' => 'string',
    ];
}
