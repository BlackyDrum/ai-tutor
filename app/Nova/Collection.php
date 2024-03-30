<?php

namespace App\Nova;

use App\Http\Controllers\ChromaController;
use App\Nova\Actions\DestroyChromaDB;
use App\Nova\Actions\ReplicateCollection;
use App\Nova\Actions\SyncChromaDB;
use App\Nova\Actions\ValidateChromaDBSync;
use App\Nova\Filters\ModuleFilter;
use App\Nova\Metrics\Collections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
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
     * @var class-string<\App\Models\Collection>
     */
    public static $model = \App\Models\Collection::class;

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
    public static $search = ['id', 'name'];

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
                ->rules('required', 'string', function (
                    $attribute,
                    $value,
                    $fail
                ) {
                    if ($message = self::checkInvalidCollectionName($value)) {
                        $fail($message);
                    }
                })
                ->creationRules('unique:collections,name')
                ->updateRules('unique:collections,name,{{resourceId}}')
                ->sortable(),

            Number::make('Max Results')
                ->min(0)
                ->rules('required', 'integer', 'gte:0')
                ->help(
                    'Specifies the maximum number of documents to embed per prompt'
                )
                ->sortable(),

            Boolean::make('Active')
                ->readonly(
                    $this->resource->active && $this->resource->module_id
                )
                ->sortable(),

            BelongsTo::make('Module', 'module', Module::class)
                ->readonly(
                    $this->resource->active && $this->resource->module_id
                )
                ->nullable()
                ->sortable(),

            HasMany::make('Embedding'),

            HasMany::make('Conversations'),

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

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        try {
            ChromaController::createCollection($model);

            self::changeActiveStatus($model);

            Log::info('App: User with ID {user-id} created a collection', [
                'id' => $model->id,
                'name' => $model->name,
            ]);
        } catch (\Exception $exception) {
            Log::error(
                'ChromaDB: Failed to create new collection with name {collection}. Reason: {reason}',
                [
                    'collection' => $model->name,
                    'reason' => $exception->getMessage(),
                ]
            );

            $model->forceDelete();

            abort(500, $exception->getMessage());
        }
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        // Since Nova doesn't offer a direct way to work with the previous model state after an update,
        // we update the ChromaDB instance related to this collection in the 'Collection' model's boot method.

        self::changeActiveStatus($model);

        Log::info('App: User with ID {user-id} updated a collection', [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        try {
            ChromaController::deleteCollection($model);

            $model->forceDelete();
        } catch (\Exception $exception) {
            Log::error(
                'ChromaDB: Failed to delete collection with name {name}. Reason: {reason}',
                [
                    'name' => $model->name,
                    'reason' => $exception->getMessage(),
                ]
            );

            $model->restore();

            abort(500, $exception->getMessage());
        }

        Log::info('App: User with ID {user-id} deleted a collection', [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }

    public function authorizedToDelete(Request $request)
    {
        return !$this->resource->active || !$this->resource->module_id;
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

    private static function changeActiveStatus($model)
    {
        if ($model->active) {
            \App\Models\Collection::query()
                ->whereNot('id', $model->id)
                ->where('module_id', $model->module_id)
                ->where('active', true)
                ->update(['active' => false]);
        }
    }

    private static function checkInvalidCollectionName($name)
    {
        if (
            !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]{1,61}[a-zA-Z0-9]$/', $name)
        ) {
            return 'The name must be 3-63 characters long, start and end with an alphanumeric character, and may contain periods, hyphens and underscores.';
        }

        if (str_contains($name, '..')) {
            return 'The name cannot contain two consecutive periods.';
        }

        if (filter_var($name, FILTER_VALIDATE_IP)) {
            return 'The name cannot be a valid IPv4 address.';
        }

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
        return [new Collections()];
    }

    public function filters(NovaRequest $request)
    {
        return [new ModuleFilter()];
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
            new ValidateChromaDBSync(),
            new SyncChromaDB(),
            new DestroyChromaDB(),
            (new ReplicateCollection())->showInline(),
        ];
    }
}
