<?php

namespace App\Nova;

use App\Nova\Dashboards\OpenAI;
use App\Nova\Filters\ModuleFilter;
use App\Nova\Metrics\Openai\TotalCosts;
use App\Nova\Metrics\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
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

            Text::make('Name')->sortable()->rules('required', 'max:255'),

            Text::make('Abbreviation')
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

            Boolean::make('Admin')->onlyOnDetail(),

            Number::make('Total Costs Generated', function ($user) {
                return $this->calculateTotalCosts($user->id);
            })
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->sortable(),

            BelongsTo::make('Module', 'module', Module::class)
                ->nullable()
                ->sortable(),

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
                ->onlyOnDetail()
                ->sortable(),

            HasMany::make('Conversations'),

            HasManyThrough::make('Messages'),
        ];
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    private function calculateTotalCosts($userId)
    {
        $models = OpenAI::models();

        $totalPrice = 0;

        foreach ($models as $model) {
            $messagesTokens = \App\Models\Message::query()
                ->join(
                    'conversations',
                    'conversations.id',
                    '=',
                    'messages.conversation_id'
                )
                ->where('conversations.user_id', '=', $userId)
                ->where('messages.openai_language_model', '=', $model->name)
                ->select([
                    DB::raw(
                        'SUM(messages.prompt_tokens) AS messages_prompt_tokens'
                    ),
                    DB::raw(
                        'SUM(messages.completion_tokens) AS messages_completion_tokens'
                    ),
                ])
                ->first();

            $conversationNameTokens = \App\Models\Conversation::query()
                ->where('user_id', '=', $userId)
                ->where('openai_language_model', '=', $model->name)
                ->select([
                    DB::raw(
                        'SUM(prompt_tokens) AS conversations_prompt_tokens'
                    ),
                    DB::raw(
                        'SUM(completion_tokens) AS conversations_completion_tokens'
                    ),
                ])
                ->first();

            $totalPrice +=
                TotalCosts::calculatePrice(
                    $messagesTokens->messages_prompt_tokens,
                    $messagesTokens->messages_completion_tokens,
                    $model->input,
                    $model->output
                ) +
                TotalCosts::calculatePrice(
                    $conversationNameTokens->conversations_prompt_tokens,
                    $conversationNameTokens->conversations_completion_tokens,
                    $model->input,
                    $model->output
                );
        }

        return '$' . number_format($totalPrice, 2);
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
        return [ExportAsCsv::make()->nameable()];
    }
}
