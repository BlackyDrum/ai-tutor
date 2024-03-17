<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

abstract class Model extends Partition
{
    // Price in dollar per million tokens
    public static float $input;
    public static float $output;

    public static string $modelName;

    public function calculate(NovaRequest $request)
    {
        $totalCompletionTokens = Messages::query()
            ->where('openai_language_model', $this->name)
            ->select(DB::raw('SUM(completion_tokens) AS total'))
            ->first();

        $totalPromptTokens = Messages::query()
            ->where('openai_language_model', $this->name)
            ->select(DB::raw('SUM(prompt_tokens) AS total'))
            ->first();

        return $this->result([
            'Prompt Tokens' => $totalPromptTokens->total,
            'Completion Tokens' => $totalCompletionTokens->total,
        ]);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return $this->name;
    }
}
