<?php

namespace App\Nova\Metrics;

use App\Models\Collection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;

class Collections extends Value
{
    public $width = '1/2';

    public $icon = 'database';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->count($request, Collection::class);
    }

    public function ranges()
    {
        return [
            'ALL' => 'All Time',
        ];
    }
}
