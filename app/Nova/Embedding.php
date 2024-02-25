<?php

namespace App\Nova;

use App\Http\Controllers\Admin\ChromaController;
use http\Env\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Embedding extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Files>
     */
    public static $model = \App\Models\Files::class;

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

            Text::make('Embedding ID')
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            Text::make('Name')
                ->onlyOnIndex()
                ->sortable(),

            File::make('File', 'path')
                ->acceptedTypes('.pdf,.txt')
                ->rules('mimes:pdf,txt', function ($attribute, $value, $fail) {
                    if (str_contains($value->getClientOriginalName(), '/') || str_contains($value->getClientOriginalName(), '\\')) {
                        $fail('The filename cannot contain the "/" character.');
                    }
                })
                ->storeOriginalName('name')
                ->storeSize('size')
                ->path('/uploads')
                ->readonly(function() {
                    return (bool)$this->resource->id;
                })
                ->disk('local'),

            Number::make('Size')
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->sortable(),

            BelongsTo::make('User')
                ->default(Auth::id())
                ->hideWhenUpdating()
                ->sortable()
                ->withMeta(['extraAttributes' => [
                    'readonly' => true
                ]]),

            BelongsTo::make('Collection')
                ->sortable()
                ->readonly(function() {
                    return (bool)$this->resource->id;
                }),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),
        ];
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        if (!ChromaController::createEmbedding($model)) {
            $model->forceDelete();
            abort(500, 'Error creating embedding');
        }
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        if (!ChromaController::deleteEmbedding($model)) {
            $model->restore();
            abort(500, 'Error deleting embedding');
        }

        $model->forceDelete();
    }

    public function authorizedToUpdate(\Illuminate\Http\Request $request)
    {
        return false;
    }

    public function authorizedToForceDelete(\Illuminate\Http\Request $request)
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
