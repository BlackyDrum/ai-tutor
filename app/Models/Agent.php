<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
