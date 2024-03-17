<?php

namespace App\Nova;

use App\Http\Controllers\ChromaController;
use App\Nova\Actions\ValidateChromaDBSync;
use App\Nova\Metrics\Collections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Collection extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Collections>
     */
    public static $model = \App\Models\Collections::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Name')
                ->rules('required', 'string')
                ->creationRules('unique:collections,name')
                ->updateRules('unique:collections,name,{{resourceId}}')
                ->sortable(),

            Number::make('Max Results')
                ->min(0)
                ->rules('required', 'integer', 'gte:0')
                ->help('Specifies the maximum number of documents to embed per prompt'),

            BelongsTo::make('Module', 'module', Module::class)
                ->nullable()
                ->creationRules('unique:collections,module_id')
                ->updateRules('unique:collections,module_id,{{resourceId}}'),

            HasMany::make('Embedding'),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),
        ];
    }


    public static function afterCreate(NovaRequest $request, Model $model)
    {
        $result = ChromaController::createCollection($model->name);

        if (!$result['status']) {
            $model->forceDelete();
            abort(500, $result['message']);
        }
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        $result = ChromaController::deleteCollection($model);

        if (!$result['status']) {
            $model->restore();
            abort(500, $result['message']);
        }

        Log::info('App: User with ID {user-id} deleted a collection', [
            'id' => $model->id,
            'name' => $model->name,
        ]);

        $model->forceDelete();
    }

    public function authorizedToForceDelete(Request $request)
    {
        return false;
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }
    public function authorizedToRestore(Request $request)
    {
        return false;
    }

    public static $group = 'ChromaDB';

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [
            new Collections()
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            ExportAsCsv::make()->nameable(),
            new ValidateChromaDBSync()
        ];
    }
}
