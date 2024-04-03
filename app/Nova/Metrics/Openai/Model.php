<?php

namespace App\Nova\Metrics\Openai;

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

        $tokens = $this->getTokens();

        $costs = Costs::calculatePrice(
            $tokens['prompt_tokens'],
            $tokens['completion_tokens'],
            $this->input,
            $this->output
        );

        $costs = number_format($costs, 2);

        $this->helpText = "Input: \${$this->input} / 1M tokens<br>Output: \${$this->output} / 1M tokens<br><b>Total costs: \$$costs</b>";
    }

    public function calculate(NovaRequest $request)
    {
        $tokens = $this->getTokens();

        return $this->result([
            'Prompt Tokens' => $tokens['prompt_tokens'],
            'Completion Tokens' => $tokens['completion_tokens'],
        ]);
    }

    private function getTokens()
    {
        $messageTokens = Costs::getTokens($this->name);

        $nameTokens = Costs::getTokens(
            modelName: $this->name,
            getTokensForConversationName: true
        );

        return [
            'prompt_tokens' =>
                $messageTokens['prompt_tokens'] + $nameTokens['prompt_tokens'],
            'completion_tokens' =>
                $messageTokens['completion_tokens'] +
                $nameTokens['completion_tokens'],
        ];
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
