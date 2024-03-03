<?php

namespace App\Nova;

use App\Http\Controllers\ChromaController;
use App\Models\Files;
use App\Nova\Metrics\Embeddings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
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
        'name',
        'embedding_id',
        'content'
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
                ->hideWhenCreating()
                ->sortable(),

            Textarea::make('Content')
                ->hideWhenCreating()
                ->updateRules('required'),

            File::make('File', 'embedding_id')
                ->acceptedTypes('.txt,.pptx,.json')
                ->disableDownload()
                ->hideFromDetail()
                ->hideWhenUpdating()
                ->rules('required', 'extensions:txt,pptx,json', function ($attribute, $value, $fail) {
                    if (str_contains($value->getClientOriginalName(), '/') || str_contains($value->getClientOriginalName(), '\\')) {
                        $fail('The filename cannot contain the "/" character.');
                    }
                })
                ->storeOriginalName('name')
                ->storeSize('size')
                ->readonly(function() {
                    return (bool)$this->resource->id;
                })
                ->disk('local'),

            Number::make('Size')
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->sortable(),

            BelongsTo::make('Collection')
                ->sortable()
                ->withoutTrashed()
                ->hideWhenUpdating()
                ->readonly(function() {
                    return (bool)$this->resource->id;
                }),

            BelongsTo::make('Parent', 'EmbeddingBelongs', Embedding::class)
                ->sortable()
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            HasMany::make('Artifacts', 'EmbeddingMany', Embedding::class)
                ->sortable()
                ->showOnDetail(function(NovaRequest $request, $resource) {
                    $count = Files::query()
                        ->where('parent_id', '=', $resource->id)
                        ->count();
                    return $count != 0; // Only show relations when file has artifacts
                }),

            BelongsTo::make('Creator', 'user', User::class)
                ->default(Auth::id())
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->sortable()
                ->withMeta(['extraAttributes' => [
                    'readonly' => true
                ]]),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),
        ];
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        $result = ChromaController::createEmbedding($model);

        if (!$result['status']) {
            $model->forceDelete();
            abort(500, $result['message']);
        }

        $model->user_id = Auth::id();

        $model->save();
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        $result = ChromaController::updateEmbedding($model);

        if (!$result['status']) {
            abort(500, $result['message']);
        }

        $model->save();
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        $result = ChromaController::deleteEmbedding($model);

        if (!$result['status']) {
            $model->restore();
            abort(500, $result['message']);
        }

        $model->forceDelete();
    }

    public function authorizedToUpdate(Request $request)
    {
        $count = Files::query()
            ->where('parent_id', '=', $this->resource->id)
            ->count();

        return $count == 0;
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
            new Embeddings()
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
        ];
    }
}
