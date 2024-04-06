<?php

namespace App\Nova;

use App\Nova\Cards\LatestMessages;
use App\Nova\Filters\LanguageModelFilter;
use App\Nova\Filters\RatingFilter;
use App\Nova\Metrics\MessagesPerDay;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Vinkla\Hashids\Facades\Hashids;

class Message extends Resource
{
    public static $globallySearchable = false;

    public static $clickAction = 'preview';

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Message>
     */
    public static $model = \App\Models\Message::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = ['id', 'user_message', 'agent_message'];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()
                ->sortable()
                ->resolveUsing(fn($value) => Hashids::decode($value)),

            BelongsTo::make('Conversation')->hideWhenUpdating(),

            Textarea::make('User Message')->alwaysShow()->showOnPreview(),

            Markdown::make('Agent Message', 'agent_message', function () {
                return htmlspecialchars_decode($this->resource->agent_message);
            })->preset(
                'github',
                new class implements Markdown\MarkdownPreset {
                    public function convert(string $content)
                    {
                        return Str::of($content)->markdown([
                            'html_input' => 'escape',
                            'allow_unsafe_links' => false,
                        ]);
                    }
                }
            )->showOnPreview(),

            Textarea::make('User Message with Context')->hideWhenUpdating(),

            Number::make('Prompt Tokens')->hideWhenUpdating()->sortable(),

            Number::make('Completion Tokens')->hideWhenUpdating()->sortable(),

            Text::make('OpenAI Language Model', 'openai_language_model')
                ->hideWhenUpdating()
                ->sortable(),

            Badge::make('Helpful')
                ->map([
                    false => 'danger',
                    true => 'success',
                ])
                ->addTypes([
                    null => 'invisible',
                ]),

            DateTime::make('Created At')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable()
                ->filterable(),

            DateTime::make('Updated At')
                ->onlyOnDetail(),
        ];
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
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
        return [(new LatestMessages())->style('tight')];
    }

    public function filters(NovaRequest $request)
    {
        return [new LanguageModelFilter(), new RatingFilter()];
    }
}
