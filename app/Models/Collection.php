<?php

namespace App\Models;

use App\Http\Controllers\ChromaController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Collection extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'max_results', 'active', 'module_id'];

    protected $hidden = ['deleted_at'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function embedding()
    {
        return $this->hasMany(Embedding::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
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
