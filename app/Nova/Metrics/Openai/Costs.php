<?php

namespace App\Nova\Metrics\Openai;

use App\Models\Conversation;
use App\Models\Message;
use App\Nova\Dashboards\OpenAI;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Nova;

class Costs extends Value
{
    public $width = 'full';

    public $icon = 'currency-dollar';

    public $name = 'Total Costs';

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
            $messageTokens = self::getTokens($model->name, $range);

            $conversationNameTokens = self::getTokens(
                $model->name,
                $range,
                true
            );

            $messagePrice = self::calculatePrice(
                $messageTokens['prompt_tokens'],
                $messageTokens['completion_tokens'],
                $model->input,
                $model->output
            );

            $namePrice = self::calculatePrice(
                $conversationNameTokens['prompt_tokens'],
                $conversationNameTokens['completion_tokens'],
                $model->input,
                $model->output
            );

            $totalPrice += $messagePrice + $namePrice;
        }

        $result = number_format($totalPrice, 2);

        return $this->result($result)->prefix('$');
    }

    public static function calculatePrice(
        $promptTokens,
        $completionTokens,
        $inputPrice,
        $outputPrice
    ) {
        return ($promptTokens / 1e6) * $inputPrice +
            ($completionTokens / 1e6) * $outputPrice;
    }

    public static function getTokens(
        $modelName,
        $range = 'ALL',
        $getTokensForConversationName = false
    ) {
        // This method distinguishes between tokens used for messages and conversation titles/names.
        // If '$getTokensForConversationName' is true, it queries the 'conversations' table to calculate
        // the total number of prompt and completion tokens used specifically for creating conversation titles
        // for a specific language model.
        // Conversely, if false, it targets the 'messages' table to compute the total tokens used for all messages
        // associated with a given language model.

        $totalTokens = ($getTokensForConversationName
            ? Conversation::query()
            : Message::query()
        )
            ->where('openai_language_model', $modelName)
            ->select([
                DB::raw('SUM(prompt_tokens) AS prompt_tokens'),
                DB::raw('SUM(completion_tokens) AS completion_tokens'),
            ]);

        if ($range != 'ALL') {
            $totalTokens->where(
                'created_at',
                '>=',
                Carbon::now()->subDays($range)
            );
        }

        return [
            'completion_tokens' => $totalTokens->first()->completion_tokens,
            'prompt_tokens' => $totalTokens->first()->prompt_tokens,
        ];
    }

    public static function calculateCostsByConversationOrUser(
        $conversationId = null,
        $userId = null
    ) {
        $models = OpenAI::models();
        $totalPrice = 0;

        // Here we calculate the total costs either per user or conversation
        // based on the provided parameter
        foreach ($models as $model) {
            $queryBase = Message::query()
                ->join(
                    'conversations',
                    'conversations.id',
                    '=',
                    'messages.conversation_id'
                )
                ->where('messages.openai_language_model', '=', $model->name);

            if ($conversationId) {
                $queryBase->where('conversations.id', '=', $conversationId);
                $conversationQueryBase = Conversation::query()->where(
                    'id',
                    '=',
                    $conversationId
                );
            } else {
                $queryBase->where('conversations.user_id', '=', $userId);
                $conversationQueryBase = Conversation::query()->where(
                    'user_id',
                    '=',
                    $userId
                );
            }

            $messagesTokens = $queryBase
                ->select([
                    DB::raw(
                        'SUM(messages.prompt_tokens) AS messages_prompt_tokens'
                    ),
                    DB::raw(
                        'SUM(messages.completion_tokens) AS messages_completion_tokens'
                    ),
                ])
                ->first();

            $conversationNameTokens = $conversationQueryBase
                ->where('openai_language_model', '=', $model->name)
                ->select([
                    DB::raw(
                        'SUM(prompt_tokens) AS conversations_prompt_tokens'
                    ),
                    DB::raw(
                        'SUM(completion_tokens) AS conversations_completion_tokens'
                    ),
                ])
                ->first();

            $totalPrice +=
                Costs::calculatePrice(
                    $messagesTokens->messages_prompt_tokens,
                    $messagesTokens->messages_completion_tokens,
                    $model->input,
                    $model->output
                ) +
                Costs::calculatePrice(
                    $conversationNameTokens->conversations_prompt_tokens,
                    $conversationNameTokens->conversations_completion_tokens,
                    $model->input,
                    $model->output
                );
        }

        return '$' . number_format($totalPrice, 2);
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
