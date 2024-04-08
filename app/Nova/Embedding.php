<?php

namespace App\Nova;

use App\Classes\ChromaDB;
use App\Models\Document;
use App\Nova\Filters\CollectionFilter;
use App\Nova\Metrics\Embeddings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
                ->resolveUsing(function ($name) {
                    return substr($name, 0, 64) .
                        (strlen($name) > 64 ? '...' : '');
                })
                ->sortable(),

            Textarea::make('Content')
                ->hideWhenCreating()
                ->updateRules('required')
                ->help(
                    $request->isUpdateOrUpdateAttachedRequest()
                        ? '<strong>Attention</strong>: Updating the resource will invalidate the current hash of the document. So, if you make changes here but then upload the document with the content identical to that before the changes, the file will not be uploaded although the documents are not identical anymore. Therefore, please ensure that any modifications made here are also reflected in the document. <strong>You may also make the necessary changes to the document itself and then upload it again instead of editing the contents here.</strong>'
                        : ''
                ),

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

            DateTime::make('Created At')->onlyOnDetail(),

            DateTime::make('Updated At')->onlyOnDetail(),
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

        $hash = md5_file($pathToFile);

        $document = Document::query()
            ->where('md5', '=', $hash)
            ->where('collection_id', '=', $collectionId)
            ->first();

        // If the same exact file was uploaded, we just clean up return
        if ($document) {
            $model->forceDelete();

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            return;
        }

        $oldDocument = Document::query()
            ->where('name', '=', $name)
            ->where('collection_id', '=', $collectionId)
            ->first();

        $newDocument = Document::query()->create([
            'name' => $name,
            'collection_id' => $collectionId,
            'md5' => $hash,
            'created_at' => $oldDocument->created_at ?? now(),
        ]);

        try {
            ChromaDB::createEmbedding($model, $newDocument, $pathToFile);

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
            ChromaDB::updateEmbedding($model);

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
            ChromaDB::deleteEmbedding($model);

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

    public function authorizedToUpdate(Request $request)
    {
        return false;
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

    public function filters(NovaRequest $request)
    {
        return [new CollectionFilter()];
    }
}
