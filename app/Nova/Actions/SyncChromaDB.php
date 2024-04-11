<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class SyncChromaDB extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Synchronize Databases';

    public $confirmText = "This action will synchronize ChromaDB with the relational database. You need to specify the authoritative data source. The selected source's data will be replicated to the other database, and existing data in the target database will be overwritten or removed. Please note that this operation might not succeed because it could take too long to execute. You should use a console and run 'php artisan chroma:sync --source=chroma' or 'php artisan chroma:sync --source=relational' instead.";

    public $standalone = true;

    public $onlyOnIndex = true;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $result = Artisan::call("chroma:sync --source={$fields->get('source')}");

        if ($result == 0) {
            return ActionResponse::message(
                'Synced ChromaDB with the relational database'
            );
        }
        else {
            return ActionResponse::danger('Syncing failed');
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Select::make('Source')->options([
                'relational' => 'Relational Database',
                'chroma' => 'ChromaDB'
            ])
            ->rules('required')
        ];
    }
}
