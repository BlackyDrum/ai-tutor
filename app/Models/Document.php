<?php

namespace App\Models;

use App\Nova\Embedding;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

class Document extends Model
{
    protected $fillable = ['name', 'md5', 'collection_id', 'created_at'];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function embeddings()
    {
        return $this->hasMany(\App\Models\Embedding::class);
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
