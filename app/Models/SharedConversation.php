<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedConversation extends Model
{
    protected $fillable = ['shared_url_id', 'conversation_id'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
