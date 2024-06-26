<?php

namespace Database\Seeders;

use App\Classes\ChromaDB;
use App\Models\Agent;
use App\Models\Collection;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $module = Module::firstOrCreate(
            [
                'ref_id' => 1214757,
            ],
            [
                'name' => 'Demo',
            ]
        );

        $user = User::firstOrCreate(
            [
                'abbreviation' => 'admin',
            ],
            [
                'name' => 'Admin',
                'password' =>
                    '$2y$12$mxS/dfJ.88hUXOLH30dOWO1udNMKse2zrPrRMrNpH1ixrkdlngltu',
                'admin' => true,
                'max_requests' => 100,
                'module_id' => $module->id,
            ]
        );

        $agent = Agent::firstOrCreate(
            [
                'name' => 'DemoAgent',
            ],
            [
                'instructions' =>
                    'You are a helpful university tutor providing aid for students tasked with programming relational database based web applications with php. always explain the code snippets you send and try to provide sources where to learn more on that subject. if in doubt, do not answer with code and ask to clarify the prompt!',
                'openai_language_model' => 'gpt-3.5-turbo-0125',
                'max_messages_included' => 12,
                'temperature' => 0.7,
                'max_response_tokens' => 1000,
                'active' => true,
                'user_id' => $user->id,
                'module_id' => $module->id,
            ]
        );

        $collection = Collection::query()->firstOrCreate(
            [
                'name' => 'DemoCollection',
            ],
            [
                'max_results' => 2,
                'module_id' => $module->id,
            ]
        );

        if ($collection->wasRecentlyCreated) {
            try {
                ChromaDB::createCollection($collection);
            } catch (\Exception $exception) {
                $collection->forceDelete();
                $user->delete();
                $module->delete();
                $agent->delete();

                throw $exception;
            }
        }
    }
}
