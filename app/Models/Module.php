<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
