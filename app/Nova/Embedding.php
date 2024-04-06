<?php

namespace App\Nova;

use App\Http\Controllers\ChromaController;
use App\Models\Document;
use App\Nova\Filters\CollectionFilter;
use App\Nova\Metrics\Embeddings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
     * @var class-string<\App\Models\Embedding>
     */
    public static $model = \App\Models\Embedding::class;

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
                ->rules('required', 'extensions:txt,pptx,json,md')
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

            BelongsTo::make('Document')
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->sortable(),

            BelongsTo::make('Collection')
                ->withoutTrashed()
                ->hideWhenUpdating()
                ->sortable(),

            DateTime::make('Created At')
                ->onlyOnDetail(),

            DateTime::make('Updated At')
                ->onlyOnDetail(),
        ];
    }

    public static function redirectAfterCreate(NovaRequest $request, $resource)
    {
        return '/resources/embeddings';
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        $pathToFile = storage_path() . '/app/' . $model->embedding_id;

        $name = $model->name;
        $collectionId = $model->collection_id;

        $newDocument = Document::query()->create([
            'name' => $model->name,
            'collection_id' => $model->collection_id,
        ]);

        try {
            ChromaController::createEmbedding($model, $newDocument);

            $oldDocument = Document::query()
                ->where('name', '=', $name)
                ->where('collection_id', '=', $collectionId)
                ->whereNot('id', '=', $newDocument->id)
                ->first();

            $oldDocument?->delete();
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

            $count = \App\Models\Embedding::query()
                ->where('document_id', '=', $model->document_id)
                ->count();

            if ($count == 0) {
                $document = Document::query()->find($model->document_id);
                $document?->delete();
            }

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
        if (
            static::authorizable() and
            Gate::check('restore', get_class(static::newModel()))
        ) {
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
}
