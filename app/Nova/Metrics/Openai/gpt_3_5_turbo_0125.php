<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class gpt_3_5_turbo_0125 extends Partition
{
    // Price in dollar per million tokens
    public static float $input = 0.50;
    public static float $output = 1.50;

    public static string $modelName = 'gpt-3.5-turbo-0125';

    public $name = 'gpt-3.5-turbo-0125';

    public $width = '1/2';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $totalCompletionTokens = Messages::query()
            ->where('openai_language_model', '=', self::$modelName)
            ->select(DB::raw('SUM(completion_tokens) AS total'))
            ->first();

        $totalPromptTokens = Messages::query()
            ->where('openai_language_model', self::$modelName)
            ->select(DB::raw('SUM(prompt_tokens) AS total'))
            ->first();

        return $this->result([
            'Prompt Tokens' => $totalPromptTokens->total,
            'Completion Tokens' => $totalCompletionTokens->total,
        ]);
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

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return self::$modelName;
    }
}
