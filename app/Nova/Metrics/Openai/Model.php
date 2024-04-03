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

        $messageTokens = Costs::getTokens($this->name);

        $conversationNameTokens = Costs::getTokens(
            modelName: $this->name,
            getTokensForConversationName: true
        );

        $messageCosts = Costs::calculatePrice(
            $messageTokens['prompt_tokens'],
            $messageTokens['completion_tokens'],
            $this->input,
            $this->output
        );

        $nameCosts = Costs::calculatePrice(
            $conversationNameTokens['prompt_tokens'],
            $conversationNameTokens['completion_tokens'],
            $this->input,
            $this->output
        );

        $costs = number_format($messageCosts + $nameCosts, 2);

        $this->helpText = "Input: \${$this->input} / 1M tokens<br>Output: \${$this->output} / 1M tokens<br><b>Total costs: \$$costs</b>";
    }

    public function calculate(NovaRequest $request)
    {
        $messageTokens = Costs::getTokens($this->name);

        $nameTokens = Costs::getTokens(
            modelName: $this->name,
            getTokensForConversationName: true
        );

        return $this->result([
            'Prompt Tokens' =>
                $messageTokens['prompt_tokens'] + $nameTokens['prompt_tokens'],
            'Completion Tokens' =>
                $messageTokens['completion_tokens'] +
                $nameTokens['completion_tokens'],
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
