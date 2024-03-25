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

    protected $fillable = ['name', 'max_results', 'module_id', 'active'];

    protected $hidden = ['deleted_at'];

    public function embedding()
    {
        return $this->hasMany(Files::class, 'collection_id');
    }

    public function conversations()
    {
        return $this->hasMany(Conversations::class, 'collection_id');
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

            try {
                ChromaController::updateCollection($oldName, $model);
            } catch (\Exception $exception) {
                Log::error(
                    'ChromaDB: Failed to update collection with name {name}. Reason: {reason}',
                    [
                        'name' => $oldName,
                        'reason' => $exception->getMessage(),
                    ]
                );

                // This in handled by Nova
                throw $exception;
            }
        });
    }
}
