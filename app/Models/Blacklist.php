<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    protected $table = 'blacklist';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
