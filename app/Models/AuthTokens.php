<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthTokens extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ref_id',
        'token'
    ];
}
