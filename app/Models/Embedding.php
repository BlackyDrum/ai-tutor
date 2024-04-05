<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Embedding extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'path',
        'size',
        'mime',
        'user_id',
        'collection_id',
        'embedding_id',
        'content',
        'document_id',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
