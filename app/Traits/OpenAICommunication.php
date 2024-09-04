<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait OpenAICommunication
{
    public function sendMessageToOpenAI(
        $systemMessage,
        $userMessage,
        $languageModel,
        $max_tokens,
        $temperature,
        $recentMessages = null,
        $usesContext = true
    ) {
        $token = config('api.openai_api_key');

        $messages = [['role' => 'system', 'content' => $systemMessage]];

        if ($recentMessages) {
            $messages = array_merge($messages, $recentMessages);
        }

        if ($usesContext) {
            $userMessage =
                "Use the context (if useful) from this or from previous messages to answer the user's question.\n\n" .
                $userMessage;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return Http::withToken($token)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $languageModel,
                'temperature' => (float) $temperature,
                'max_tokens' => (int) $max_tokens,
                'messages' => $messages,
            ]);
    }

    public function getQuizDataFromOpenAI($topic, $difficulty, $count)
    {
        $token = config('api.openai_api_key');

        $prompt = "Give me $count multiple choice questions for a quiz about $topic at an $difficulty level. Always use different and unique questions.";

        $format = [
            'questions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => [
                            'type' => 'string',
                        ],
                        'correct_answer' => [
                            'type' => 'string',
                        ],
                        'wrong_answer_a' => [
                            'type' => 'string',
                        ],
                        'wrong_answer_b' => [
                            'type' => 'string',
                        ],
                        'wrong_answer_c' => [
                            'type' => 'string',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                    ],
                    'additionalProperties' => false,
                    'required' => ['question', 'correct_answer', 'wrong_answer_a', 'wrong_answer_b', 'wrong_answer_c', 'description'],
                ]
            ]
        ];

        $messages = [
            ['role' => 'system', 'content' => $prompt],
        ];

        $responseFormat = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'quiz',
                'schema' => [
                    'type' => 'object',
                    'properties' => $format,
                    'additionalProperties' => false,
                    'required' => ['questions'],
                ],
                'strict' => true
            ]
        ];

        return Http::withToken($token)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('api.openai_quiz_model'),
                'temperature' => 1.0,
                'messages' => $messages,
                'response_format' => $responseFormat,
            ]);
    }
}
