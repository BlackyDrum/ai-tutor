<?php

namespace App\Nova\Metrics;

use App\Models\Embedding;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;

class Embeddings extends Value
{
    public $width = '1/2';

    public $icon = 'document-text';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        return $this->result(Embedding::query()->count());
    }

    public function ranges()
    {
        return [
            'ALL' => 'All Time',
        ];
    }
}
