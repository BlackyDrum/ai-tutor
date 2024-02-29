<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationHasDocument extends Model
{
    use HasFactory;

    protected $table = 'conversation_has_document';

    protected $fillable = [
        'conversation_id',
        'file_id'
    ];
}
