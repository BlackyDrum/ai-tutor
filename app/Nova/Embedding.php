<?php

namespace App\Nova;

use App\Http\Controllers\ChromaController;
use App\Nova\Filters\CollectionFilter;
use App\Nova\Metrics\Embeddings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\File;
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
    public static $search = ['id', 'name', 'embedding_id', 'content'];

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

            Text::make('Embedding ID')->hideWhenCreating()->hideWhenUpdating(),

            Text::make('Name')
                ->hideWhenCreating()
                ->updateRules('required')
                ->sortable(),

            Textarea::make('Content')
                ->hideWhenCreating()
                ->updateRules('required'),

            File::make('File', 'embedding_id')
                ->acceptedTypes('.txt,.pptx,.json,.md')
                ->disableDownload()
                ->hideFromDetail()
                ->hideWhenUpdating()
                ->rules('required', 'extensions:txt,pptx,json,md', function (
                    $attribute,
                    $value,
                    $fail
                ) {
                    if (
                        str_contains($value->getClientOriginalName(), '/') ||
                        str_contains($value->getClientOriginalName(), '\\')
                    ) {
                        $fail('The filename cannot contain the "/" character.');
                    }
                })
                ->storeOriginalName('name')
                ->storeSize('size')
                ->readonly(function () {
                    return (bool) $this->resource->id;
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
                ->readonly(function () {
                    return (bool) $this->resource->id;
                }),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->onlyOnDetail()
                ->sortable(),

            DateTime::make('Updated At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->onlyOnDetail()
                ->sortable(),
        ];
    }

    public static function redirectAfterCreate(NovaRequest $request, $resource)
    {
        return '/resources/embeddings';
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        $pathToFile = storage_path() . '/app/' . $model->embedding_id;

        try {
            ChromaController::createEmbedding($model);

            Log::info('App: User with ID {user-id} created an embedding', [
                'id' => $model->id,
                'name' => $model->name,
                'embedding-id' => $model->embedding_id,
            ]);
        } catch (\Exception $exception) {
            $model->forceDelete();

            abort(500, $exception->getMessage());
        } finally {
            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }
        }
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        try {
            ChromaController::updateEmbedding($model);

            Log::info('App: User with ID {user-id} updated an embedding', [
                'id' => $model->id,
                'name' => $model->name,
                'embedding-id' => $model->embedding_id,
            ]);
        } catch (\Exception $exception) {
            Log::error(
                'ChromaDB: Failed to update embedding with ID {embedding-id}. Reason: {reason}',
                [
                    'embedding-id' => $model->embedding_id,
                    'collection-id' => $model->collection_id,
                    'reason' => $exception->getMessage(),
                ]
            );

            abort(500, $exception->getMessage());
        }
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        try {
            ChromaController::deleteEmbedding($model);

            Log::info('App: User with ID {user-id} deleted an embedding', [
                'id' => $model->id,
                'name' => $model->name,
                'embedding-id' => $model->embedding_id,
            ]);

            $model->forceDelete();
        } catch (\Exception $exception) {
            Log::error(
                'ChromaDB: Failed to delete embedding with ID {embedding-id}. Reason: {reason}',
                [
                    'embedding-id' => $model->embedding_id,
                    'collection-id' => $model->collection_id,
                    'reason' => $exception->getMessage(),
                ]
            );

            $model->restore();

            abort(500, $exception->getMessage());
        }
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

    public static function softDeletes()
    {
        if (static::authorizable() and Gate::check('restore', get_class(static::newModel()))) {
            return parent::softDeletes();
        }

        return false;
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [new Embeddings()];
    }

    public function filters(NovaRequest $request)
    {
        return [new CollectionFilter()];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [ExportAsCsv::make()->nameable()];
    }
}
