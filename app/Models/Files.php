<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Files extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'path',
        'size',
        'mime',
        'user_id'
    ];

    protected $casts = [
        'id' => 'string'
    ];
}
