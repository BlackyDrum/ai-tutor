<?php

namespace App\Nova\Metrics;

use App\Models\Message;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class Ratings extends Partition
{
    public $width = '1/3';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $positive = Message::query()->where('helpful', true)->count();

        $negative = Message::query()->where('helpful', false)->count();

        $neutral = Message::query()->whereNull('helpful')->count();

        return $this->result([
            'Helpful' => $positive,
            'Not Helpful' => $negative,
            'Not Rated' => $neutral,
        ])->colors([
            'Helpful' => '#66ff66',
            'Not Helpful' => '#ff6666',
            'Not Rated' => '#ffffff',
        ]);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'ratings';
    }
}
