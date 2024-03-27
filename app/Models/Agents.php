<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agents extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'context',
        'first_message',
        'response_shape',
        'instructions',
        'active',
        'user_id',
        'module_id',
        'temperature',
        'max_response_tokens',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Modules::class, 'module_id');
    }

    public function conversations()
    {
        return $this->hasMany(Conversations::class, 'agent_id');
    }
}
