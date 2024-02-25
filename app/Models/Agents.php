<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agents extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'api_id',
        'name',
        'context',
        'first_message',
        'response_shape',
        'instructions',
        'active',
        'user_id'
    ];

    protected $casts = [
        'api_id' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversations::class, 'agent_id');
    }
}
