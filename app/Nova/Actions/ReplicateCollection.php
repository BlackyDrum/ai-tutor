<?php

namespace App\Nova\Actions;

use App\Classes\ChromaDB;
use App\Models\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

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
    public function handle(ActionFields $fields, $models)
    {
        $model = $models[0];

        $replication = Collection::query()->create([
            'name' => $fields->get('name'),
            'max_results' => $model->max_results,
            'active' => false,
            'module_id' => $model->module_id,
        ]);

        try {
            ChromaDB::replicateCollection(
                original: $model,
                copy: $replication
            );

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

    public function fields(NovaRequest $request)
    {
        return [
            Text::make('Name')->rules(
                'required',
                'string',
                'unique:collections,name',
                function ($attribute, $value, $fail) {
                    if (
                        $message = \App\Nova\Collection::checkInvalidCollectionName(
                            $value
                        )
                    ) {
                        $fail($message);
                    }
                }
            ),
        ];
    }
}
