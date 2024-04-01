<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Collection;
use App\Models\Module;
use App\Models\User;
use App\Nova\Dashboards\OpenAI;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::random(rand(10,40)),
            'url_id' => Str::orderedUuid(),
            'prompt_tokens' => rand(100,1000),
            'completion_tokens' => rand(2,10),
            'openai_language_model' => (OpenAI::models()[rand(0, count(OpenAI::models()) - 1)])->name,
            'agent_id' => Agent::query()->first(),
            'user_id' => User::query()->first(),
            'collection_id' => Collection::query()->first(),
            'module_id' => Module::query()->first(),
        ];
    }
}
