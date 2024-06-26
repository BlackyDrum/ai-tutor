<?php

namespace App\Nova;

use App\Nova\Filters\AgentFilter;
use App\Nova\Filters\CollectionFilter;
use App\Nova\Filters\ModuleFilter;
use App\Nova\Metrics\ConversationsPerDay;
use App\Nova\Metrics\Openai\Costs;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

class Conversation extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Conversation>
     */
    public static $model = \App\Models\Conversation::class;

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
    public static $search = ['id', 'url_id', 'name'];

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
                ->resolveUsing(function ($title) {
                    return substr($title, 0, 64) .
                        (strlen($title) > 64 ? '...' : '');
                })
                ->sortable(),

            URL::make('URL', fn() => "/peek/{$this->url_id}")->displayUsing(
                fn() => 'Show'
            ),

            Text::make('URL ID')->onlyOnDetail(),

            Text::make('Name Created By', 'openai_language_model')
                ->onlyOnDetail()
                ->sortable(),

            Text::make('Name Prompt Tokens', 'prompt_tokens')
                ->onlyOnDetail()
                ->sortable(),

            Text::make('Name Completion Tokens', 'completion_tokens')
                ->onlyOnDetail()
                ->sortable(),

            Boolean::make('Name Edited')->onlyOnDetail(),

            Number::make('Total Costs', function ($conversation) {
                return Costs::calculateCostsByConversationOrUser(
                    conversationId: $conversation->id
                );
            }),

            BelongsTo::make('Module')->sortable(),

            BelongsTo::make('Agent')->sortable(),

            BelongsTo::make('Collection')->sortable(),

            BelongsTo::make('Owner', 'user', User::class)
                ->sortable()
                ->filterable(),

            HasMany::make('Messages'),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),

            DateTime::make('Updated At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->onlyOnDetail()
                ->sortable(),
        ];
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    public function authorizedToUpdate(Request $request)
    {
        return false;
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    public function filters(NovaRequest $request)
    {
        return [new ModuleFilter(), new CollectionFilter(), new AgentFilter()];
    }
}
