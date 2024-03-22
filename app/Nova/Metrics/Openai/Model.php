<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Messages;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

abstract class Model extends Partition
{
    // Price in dollar per million tokens
    public float $input;
    public float $output;

    public function __construct($component = null)
    {
        parent::__construct($component);

        $tokens = TotalCosts::getTokens($this->name);

        $costs = number_format(
            TotalCosts::calculatePrice(
                $tokens['prompt_tokens'],
                $tokens['completion_tokens'],
                $this->input,
                $this->output
            ),
            2
        );

        $this->helpText = "Input: \${$this->input} / 1M tokens<br>Output: \${$this->output} / 1M tokens<br><b>Total costs: \$$costs</b>";
    }

    public function calculate(NovaRequest $request)
    {
        $tokens = TotalCosts::getTokens($this->name);

        return $this->result([
            'Prompt Tokens' => $tokens['prompt_tokens'],
            'Completion Tokens' => $tokens['completion_tokens'],
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
