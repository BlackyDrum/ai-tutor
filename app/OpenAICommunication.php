<?php

namespace App;

use Illuminate\Support\Facades\Http;

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
}
