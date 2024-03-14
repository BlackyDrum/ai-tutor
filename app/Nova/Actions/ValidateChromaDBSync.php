<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class ValidateChromaDBSync extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Check sync';

    public $confirmText = 'This action will verify the synchronization of collections and embeddings between our relational database and ChromaDB';

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
        $exitCode = Artisan::call('chroma:check');

        if ($exitCode == 1) {
            $message = 'Relational database is in sync with ChromaDB';
            return ActionResponse::message($message);
        }
        else {
            $message = "Relational database is NOT in sync with ChromaDB. Use 'php artisan chroma:check' on a command line for more information";
            return ActionResponse::danger($message);
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
        return [];
    }
}
