<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationHasDocument extends Model
{
    protected $table = 'conversation_has_document';

    protected $fillable = ['conversation_id', 'embedding_id'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function embedding()
    {
        return $this->belongsTo(Embedding::class);
    }
}
