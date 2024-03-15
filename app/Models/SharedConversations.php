<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedConversations extends Model
{
    use HasFactory;

    protected $fillable = [
        'shared_url_id',
        'conversation_id'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversations::class, 'conversation_id');
    }
}
