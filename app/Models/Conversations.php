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
}
