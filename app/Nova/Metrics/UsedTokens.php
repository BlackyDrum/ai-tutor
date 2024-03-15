<?php

namespace App\Nova\Metrics;

use App\Models\Messages;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Nova;

class UsedTokens extends Value
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $result = Messages::query()
            ->select(DB::raw('SUM(prompt_tokens + completion_tokens) as total'));

        if ($request->input('range') != 'ALL') {
            $result->where('created_at', '>=', Carbon::now()->subDays($request->input('range')));
        }

        $result = $result->first();

        return $this->result($result->total);
    }

    public $name = 'Total Tokens';

    public $width = '1/2';

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            7 => Nova::__('7 Days'),
            14 => Nova::__('14 Days'),
            30 => Nova::__('30 Days'),
            60 => Nova::__('60 Days'),
            90 => Nova::__('90 Days'),
            180 => Nova::__('180 Days'),
            'ALL' => Nova::__('All Time'),
        ];
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return \DateTimeInterface|\DateInterval|float|int|null
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }
}
