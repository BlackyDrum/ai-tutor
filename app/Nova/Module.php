<?php

namespace App\Nova;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasManyThrough;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Module extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Module>
     */
    public static $model = \App\Models\Modules::class;

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
    public static $search = ['id', 'name', 'ref_id'];

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
                ->rules('required')
                ->creationRules('unique:modules,name')
                ->updateRules('unique:modules,name,{{resourceId}}'),

            Text::make('Ref ID')
                ->sortable()
                ->rules('required', 'integer')
                ->creationRules('unique:modules,ref_id')
                ->updateRules('unique:modules,ref_id,{{resourceId}}')
                ->help('Unique Ref ID for an ILIAS course'),

            HasManyThrough::make('Conversations'),

            HasMany::make('User', 'user', User::class),
        ];
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        Log::info('App: User with ID {user-id} deleted a module', [
            'id' => $model->id,
            'name' => $model->name,
            'ref-id' => $model->ref_id,
        ]);
    }

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
        return [];
    }
}
