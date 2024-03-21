<?php

namespace App\Models;

use App\Http\Controllers\ChromaController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Collections extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'max_results', 'module_id'];

    protected $hidden = ['deleted_at'];

    public function embedding()
    {
        return $this->hasMany(Files::class, 'collection_id');
    }

    public function module()
    {
        return $this->belongsTo(Modules::class, 'module_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function (Model $model) {
            $oldName = $model->getOriginal('name');

            ChromaController::updateCollection($oldName, $model);
        });
    }
}
