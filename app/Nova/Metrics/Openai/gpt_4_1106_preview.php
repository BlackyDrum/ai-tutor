<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class gpt_4_1106_preview extends Partition
{
    // Price in dollar per million tokens
    public static float $input = 10.0;
    public static float $output = 30.0;

    public static string $modelName = 'gpt-4-1106-preview';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $totalCompletionTokens = Messages::query()
            ->where('openai_language_model', self::$modelName)
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

    public $name = 'gpt-4-1106-preview';

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
