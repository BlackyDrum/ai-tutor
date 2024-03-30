<?php

namespace App\Nova;

use App\Nova\Dashboards\OpenAI;
use App\Nova\Filters\ModuleFilter;
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
     * @var class-string<\App\Models\Agent>
     */
    public static $model = \App\Models\Agent::class;

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
                ->help($this->helpText)
                ->sortable(),

            Number::make('Max Messages Included')
                ->default(12)
                ->rules('required', 'integer', 'gte:0')
                ->help(
                    'Limits the number of previous messages considered for context in an ongoing conversation'
                ),

            Number::make('Temperature')
                ->default(0.7)
                ->step(0.1)
                ->min(0)
                ->max(1)
                ->rules('required', 'numeric', 'between:0,1')
                ->help(
                    'Specifies how deterministic the agent should answer. Higher values like 0.8 will make the output more random, while lower values like 0.2 will make it more focused and deterministic'
                ),

            Number::make('Max Response Tokens')
                ->default(1000)
                ->min(0)
                ->max(4096)
                ->rules('required', 'integer', 'between:0,4096')
                ->help(
                    'The maximum number of tokens that can be generated in the chat completion'
                ),

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
            \App\Models\Agent::query()
                ->whereNot('id', $model->id)
                ->where('module_id', $model->module_id)
                ->where('active', true)
                ->update(['active' => false]);
        }
    }

    public function filters(NovaRequest $request)
    {
        return [new ModuleFilter()];
    }
}
