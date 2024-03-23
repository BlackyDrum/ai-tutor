<?php

namespace App\Nova;

use App\Http\Controllers\HomeController;
use App\Models\Agents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Agent extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Agents>
     */
    public static $model = \App\Models\Agents::class;

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
                ->rules(
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('agents', 'name')->ignore($this->resource->id)
                )
                ->sortable(),

            /*

            Text::make('Context')
                ->rules('required','string','max:255')
                ->hideFromIndex()
                ->hideWhenUpdating()
                ->readonly(function() {
                    return (bool)$this->resource->id;
                }),

            Text::make('First Message')
                ->rules('required','string','max:255')
                ->hideFromIndex()
                ->hideWhenUpdating()
                ->readonly(function() {
                    return (bool)$this->resource->id;
                }),

            Text::make('Response Shape')
                ->rules('required','string','max:255')
                ->hideFromIndex()
                ->hideWhenUpdating()
                ->readonly(function() {
                    return (bool)$this->resource->id;
                }),

            */

            Textarea::make('Instructions')
                ->rules('required', 'string')
                ->help(
                    'Guidelines that the agent follows to generate responses'
                ),

            Boolean::make('Active')->readonly(
                $this->resource->active && $this->resource->module_id
            ),

            BelongsTo::make('Module', 'module', Module::class)
                ->readonly(
                    $this->resource->active && $this->resource->module_id
                )
                ->nullable(),

            BelongsTo::make('Creator', 'user', User::class)
                ->default(Auth::id())
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->sortable()
                ->withMeta([
                    'extraAttributes' => [
                        'readonly' => true,
                    ],
                ]),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),

            DateTime::make('Updated At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),
        ];
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        self::changeActiveStatus($model);
    }

    public function authorizedToDelete(Request $request)
    {
        return !$this->resource->active || !$this->resource->module_id;
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        $model->user_id = Auth::id();
        $model->save();

        self::changeActiveStatus($model);
    }

    private static function changeActiveStatus($model)
    {
        if ($model->active) {
            Agents::query()
                ->whereNot('id', $model->id)
                ->where('module_id', $model->module_id)
                ->where('active', true)
                ->update(['active' => false]);
        }
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        Log::info('App: User with ID {user-id} deleted an agent', [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }

    public static $group = 'Chat';

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
        return [ExportAsCsv::make()->nameable()];
    }
}
