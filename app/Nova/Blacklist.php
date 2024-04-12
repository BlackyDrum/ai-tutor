<?php

namespace App\Nova;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class Blacklist extends Resource
{
    public static $globallySearchable = false;
    public static $searchable = false;

    public static $clickAction = 'preview';

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Blacklist>
     */
    public static $model = \App\Models\Blacklist::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'abbreviation';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = ['id'];

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

            Text::make('Abbreviation')
                ->rules(
                    'required',
                    Rule::unique('blacklist', 'abbreviation')->ignore(
                        $this->resource->id
                    )
                )
                ->help('ILIAS Username, e.g <strong>ga9102s</strong>')
                ->showOnPreview()
                ->sortable(),

            Textarea::make('Reason')->nullable()->showOnPreview()->alwaysShow(),

            BelongsTo::make('Banned By', 'user', User::class)
                ->hideWhenUpdating()
                ->hideWhenCreating()
                ->nullable(),

            DateTime::make('Banned At', 'created_at')
                ->hideWhenCreating()
                ->hideWhenUpdating()
                ->sortable(),

            DateTime::make('Updated At')->onlyOnDetail(),
        ];
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        $model->user_id = Auth::id();
        $model->save();

        Log::info('App: User with ID {user-id} banned {banned-user}', [
            'banned-user' => $model->abbreviation
        ]);
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }
}
