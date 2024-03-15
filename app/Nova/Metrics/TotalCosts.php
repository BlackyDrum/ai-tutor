<?php

namespace App\Nova\Metrics;

use App\Models\Messages;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Nova;

class TotalCosts extends Value
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $totalPromptTokens = Messages::query()
            ->select(DB::raw('SUM(prompt_tokens) AS total'));

        $totalCompletionTokens = Messages::query()
            ->select(DB::raw('SUM(completion_tokens) AS total'));

        if ($request->input('range') != 'ALL') {
            $totalPromptTokens->where('created_at', '>=', Carbon::now()->subDays($request->input('range')));
            $totalCompletionTokens->where('created_at', '>=', Carbon::now()->subDays($request->input('range')));
        }

        $result = match (config('api.openai_language_model')) {
            'gpt-3.5-turbo-0125' => (($totalPromptTokens->first()->total / 1e6) * 0.5) + (($totalCompletionTokens->first()->first / 1e6) * 1.5),
            'gpt-3.5-turbo-instruct' => (($totalPromptTokens->first()->total / 1e6) * 1.5) + (($totalCompletionTokens->first()->first / 1e6) * 2.0),
            'gpt-4' => (($totalPromptTokens->first()->total / 1e6) * 30.0) + (($totalCompletionTokens->first()->first / 1e6) * 60.0),
            'gpt-4-32k' => (($totalPromptTokens->first()->total / 1e6) * 60.0) + (($totalCompletionTokens->first()->first / 1e6) * 120.0),
            'gpt-4-1106-preview', 'gpt-4-1106-vision-preview', 'gpt-4-0125-preview' => (($totalPromptTokens->first()->total / 1e6) * 10.0) + (($totalCompletionTokens->first()->first / 1e6) * 30.0),
            default => 0,
        };

        return $this->result(number_format($result, 2))->prefix('$');
    }

    public $icon = 'currency-dollar';

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
