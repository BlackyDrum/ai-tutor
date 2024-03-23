<?php

namespace App\Nova\Actions;

use App\Http\Controllers\ChromaController;
use App\Models\Collections;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class ReplicateCollection extends Action
{
    use InteractsWithQueue, Queueable;

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

        $replication = Collections::query()->create([
            'name' => $model->name . time(),
            'max_results' => $model->max_results,
            'active' => false,
            'module_id' => $model->module_id,
        ]);

        $result = ChromaController::replicateCollection($model, $replication);

        if (!$result['status']) {
            $replication->forceDelete();
            return ActionResponse::danger(
                "Failed to replicate collection. Reason: {$result['message']}"
            );
        }

        return ActionResponse::message(
            "Collection replicated as {$replication->name}"
        );
    }
}
