<?php

namespace App\Nova\Actions;

use App\Http\Controllers\ChromaController;
use App\Models\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;

class ReplicateCollection extends Action
{
    use InteractsWithQueue, Queueable;

    public $confirmText = 'This action will create a copy of the selected collection and its embeddings';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $model = $models[0];

        $replication = Collection::query()->create([
            'name' => $model->name . time(),
            'max_results' => $model->max_results,
            'active' => false,
            'module_id' => $model->module_id,
        ]);

        try {
            ChromaController::replicateCollection(original: $model, copy: $replication);

            Log::info(
                'User with ID {user-id} replicated a collection with name {name}',
                [
                    'name' => $model->name,
                ]
            );

            return ActionResponse::message(
                "Collection replicated as {$replication->name}"
            );
        } catch (\Exception $exception) {
            Log::error(
                'ChromaDB: Failed to replicate collection with name {name}. Reason: {reason}',
                [
                    'name' => $model->name,
                    'reason' => $exception->getMessage(),
                ]
            );

            $replication->forceDelete();

            return ActionResponse::danger(
                "Failed to replicate collection. Reason: {$exception->getMessage()}"
            );
        }
    }
}
