<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use App\Nova\Dashboards\OpenAI;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Nova;

class TotalCosts extends Value
{
    public $width = 'full';

    public $icon = 'currency-dollar';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $range = $request->input('range');

        $models = OpenAI::models();

        $totalPrice = 0;

        foreach ($models as $model) {
            $tokens = self::getTokens($model->name, $range);
            $price = self::calculatePrice(
                $tokens['prompt_tokens'],
                $tokens['completion_tokens'],
                $model->input,
                $model->output
            );
            $totalPrice += $price;
        }

        $result = number_format($totalPrice, 2);

        return $this->result($result)->prefix('$');
    }

    public static function calculatePrice($promptTokens, $completionTokens, $inputPrice, $outputPrice)
    {
        return (($promptTokens / 1e6) * $inputPrice) + (($completionTokens / 1e6) * $outputPrice);
    }

    public static function getTokens($modelName, $range = 'ALL')
    {
        $totalPromptTokens = Messages::query()
            ->where('openai_language_model', $modelName)
            ->select(DB::raw('SUM(prompt_tokens) AS total'));

        $totalCompletionTokens = Messages::query()
            ->where('openai_language_model', $modelName)
            ->select(DB::raw('SUM(completion_tokens) AS total'));

        if ($range != 'ALL') {
            $totalPromptTokens
                ->where('created_at', '>=', Carbon::now()->subDays($range));
            $totalCompletionTokens
                ->where('created_at', '>=', Carbon::now()->subDays($range));
        }

        return [
            'completion_tokens' => $totalCompletionTokens->first()->total,
            'prompt_tokens' => $totalPromptTokens->first()->total,
        ];
    }

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
