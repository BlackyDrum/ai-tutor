<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;

class SharedConversation extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\SharedConversations>
     */
    public static $model = \App\Models\SharedConversations::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'shared_url_id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = ['id', 'shared_url_id'];

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

            URL::make(
                'URL',
                fn() => "/share/{$this->shared_url_id}"
            )->displayUsing(fn() => 'Show'),

            Text::make('Shared URL ID', 'shared_url_id'),

            BelongsTo::make('Conversation'),
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
}
