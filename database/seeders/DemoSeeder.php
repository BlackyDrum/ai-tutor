<?php

namespace Database\Seeders;

use App\Http\Controllers\ChromaController;
use App\Http\Controllers\HomeController;
use App\Models\Agents;
use App\Models\Collections;
use App\Models\Modules;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $module = Modules::firstOrCreate(
            [
                'ref_id' => 1214757,
            ],
            [
                'name' => 'Demo',
                'openai_language_model' => 'gpt-3.5-turbo-0125'
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
                'temperature' => 0.7,
                'max_response_tokens' => 1000,
                'module_id' => $module->id,
            ]
        );

        $agent = Agents::firstOrCreate(
            [
                'name' => 'DemoAgent',
            ],
            [
                'instructions' =>
                    'You are a helpful university tutor providing aid for students tasked with programming relational database based web applications with php. always explain the code snippets you send and try to provide sources where to learn more on that subject. if in doubt, do not answer with code and ask to clarify the prompt!',
                'active' => true,
                'user_id' => $user->id,
                'module_id' => $module->id,
            ]
        );

        $collection = Collections::query()->firstOrCreate(
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
                ChromaController::createCollection($collection);
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
