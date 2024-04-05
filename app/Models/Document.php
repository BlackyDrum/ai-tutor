<?php

namespace App\Models;

use App\Nova\Embedding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Http\Requests\NovaRequest;

class Document extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'collection_id'];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function embeddings()
    {
        return $this->hasMany(\App\Models\Embedding::class, 'document_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Model $model) {
            $embeddings = \App\Models\Embedding::query()
                ->where('document_id', '=', $model->id)
                ->get();

            foreach ($embeddings as $embedding) {
                $embedding->delete();

                Embedding::afterDelete(new NovaRequest(), $embedding);
            }
        });
    }
}
