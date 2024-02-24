<?php

namespace App\Nova;

use App\Http\Controllers\Admin\ChromaController;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
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
                ->rules('required', 'string', 'max:10', 'unique:collections,name')
                ->readonly(function() {
                    return (bool)$this->resource->id;
                }),

            HasMany::make('Embedding'),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating(),
        ];
    }


    public static function afterCreate(NovaRequest $request, Model $model)
    {
        if (!ChromaController::createCollection($model->name)) {
            $model->delete();
            abort(500, 'Error creating collection');
        }
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        if (!ChromaController::deleteCollection($model)) {
            abort(500, 'Error deleting collection');
        }

        $model->forceDelete();
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
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
        return [];
    }
}
