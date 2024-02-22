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
        'creating_user'
    ];

    protected $casts = [
        'id' => 'string'
    ];
}
