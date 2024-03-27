<?php

namespace App\Nova;

use App\Models\Agents;
use App\Nova\Dashboards\OpenAI;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
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

    public string $helpText = '';

    public function __construct($resource = null)
    {
        parent::__construct($resource);

        $models = OpenAI::models();

        foreach ($models as $model) {
            $this->helpText .= "<strong>{$model->name}</strong> - Input: \${$model->input} / 1M tokens, Output: \${$model->output} / 1M tokens<br>";
        }
    }

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

            Textarea::make('Instructions')
                ->rules('required', 'string')
                ->help(
                    'Guidelines that the agent follows to generate responses'
                ),

            Select::make('OpenAI Language Model', 'openai_language_model')
                ->rules('required', 'string')
                ->options(function () {
                    $models = OpenAI::models();

                    return array_column($models, 'name', 'name');
                })
                ->help($this->helpText),

            Number::make('Max Messages Included')
                ->rules('required', 'integer', 'gte:0')
                ->help(
                    'Limits the number of previous messages considered for context in an ongoing conversation'
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

            HasMany::make('Conversations'),

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

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        $model->user_id = Auth::id();
        $model->save();

        self::changeActiveStatus($model);

        Log::info('App: User with ID {user-id} created an agent', [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        self::changeActiveStatus($model);

        Log::info('App: User with ID {user-id} updated an agent', [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        Log::info('App: User with ID {user-id} deleted an agent', [
            'id' => $model->id,
            'name' => $model->name,
        ]);
    }

    public function replicate()
    {
        return tap(parent::replicate(), function ($resource) {
            $model = $resource->model();

            $model->active = false;
        });
    }

    public function authorizedToDelete(Request $request)
    {
        return !$this->resource->active || !$this->resource->module_id;
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
