<?php

namespace App\Nova;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Module extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Module>
     */
    public static $model = \App\Models\Module::class;

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
                ->rules(
                    'required',
                    Rule::unique('modules', 'name')->ignore($this->resource->id)
                ),

            Text::make('Ref ID')
                ->rules(
                    'required',
                    'integer',
                    Rule::unique('modules', 'ref_id')->ignore($this->resource->id)
                )
                ->help('Unique Ref ID for an ILIAS course')
                ->sortable(),

            DateTime::make('Created At')
                ->onlyOnDetail(),

            DateTime::make('Updated At')
                ->onlyOnDetail(),

            HasMany::make('Conversations'),

            HasMany::make('User', 'user', User::class),
        ];
    }

    public static function afterCreate(NovaRequest $request, Model $model)
    {
        Log::info('App: User with ID {user-id} created a module', [
            'id' => $model->id,
            'name' => $model->name,
            'ref-id' => $model->ref_id,
        ]);
    }

    public static function afterUpdate(NovaRequest $request, Model $model)
    {
        Log::info('App: User with ID {user-id} updated a module', [
            'id' => $model->id,
            'name' => $model->name,
            'ref-id' => $model->ref_id,
        ]);
    }

    public static function afterDelete(NovaRequest $request, Model $model)
    {
        Log::info('App: User with ID {user-id} deleted a module', [
            'id' => $model->id,
            'name' => $model->name,
            'ref-id' => $model->ref_id,
        ]);
    }

    public function authorizedToReplicate(Request $request)
    {
        return false;
    }
}
