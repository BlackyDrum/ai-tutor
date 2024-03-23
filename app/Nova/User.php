<?php

namespace App\Nova;

use App\Nova\Metrics\Users;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasManyThrough;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\User>
     */
    public static $model = \App\Models\User::class;

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
                ->sortable()
                ->rules('required', 'max:255')
                ->creationRules('unique:users,name')
                ->updateRules('unique:users,name,{{resourceId}}'),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', Rules\Password::defaults())
                ->updateRules('nullable', Rules\Password::defaults()),

            Number::make('Max Requests')
                ->rules('required', 'min:0')
                ->min(0)
                ->default(function () {
                    return config('chat.max_requests');
                })
                ->help('Maximum number of requests per day'),

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

            Boolean::make('Admin'),

            BelongsTo::make('Module', 'module', Module::class)->nullable(),

            DateTime::make('Terms Accepted At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),

            DateTime::make('Updated At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->hideFromIndex()
                ->sortable(),

            HasMany::make('Conversations'),

            HasManyThrough::make('Messages'),
        ];
    }

    public function authorizedToReplicate(Request $request)
    {
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
        return [new Users()];
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
