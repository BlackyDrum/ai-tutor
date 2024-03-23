<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modules extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'ref_id', 'temperature'];

    public function agent()
    {
        return $this->hasMany(Agents::class, 'module_id');
    }

    public function user()
    {
        return $this->hasMany(User::class, 'module_id');
    }

    public function conversations()
    {
        return $this->hasManyThrough(
            Conversations::class,
            User::class,
            'module_id',
            'user_id'
        );
    }
}
