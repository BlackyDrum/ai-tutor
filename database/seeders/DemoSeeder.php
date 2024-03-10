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
        $module = Modules::query()->create([
            'name' => 'Demo',
            'ref_id' => 1214757,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ]);

        $user = User::query()->create([
            'name' => 'admin',
            'password' => '$2y$12$/NMljmWG.5fUFtpGtFihiu4N49eIoU.CYMRtH7YG6tCqaGTlXrsvm',
            'admin' => true,
            'max_requests' => 100,
            'module_id' => $module->id,
        ]);

        $token = HomeController::getBearerToken();

        if (is_array($token)) {
            $user->delete();
            $module->delete();
            abort(500, $token['reason']);
        }

        try {
            $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/create-agent', [
                'name' => 'DemoAgent_' . time(),
                'context' => 'Testing GPT with Embeddings',
                'first_message' => 'Hello.',
                'response_shape' => 'Always provide code samples.',
                'instructions' => 'You are a helpful university tutor providing aid for students tasked with programming relational database based web applications with php. always explain the code snippets you send and try to provide sources where to learn more on that subject. if in doubt, do not answer with code and ask to clarify the prompt!',
                'creating_user' => config('api.username'),
            ]);
        } catch (\Exception $exception) {
            $user->delete();
            $module->delete();
            abort(500, $exception->getMessage());
        }


        if ($response->failed()) {
            $user->delete();
            $module->delete();
            abort(500, $response->reason());
        }

        $agent = Agents::query()->create([
            'api_id' => $response->json()['id'],
            'name' => 'DemoAgent_' . time(),
            'context' => 'Testing GPT with Embeddings',
            'first_message' => 'Hello.',
            'response_shape' => 'Always provide code samples.',
            'instructions' => 'You are a helpful university tutor providing aid for students tasked with programming relational database based web applications with php. always explain the code snippets you send and try to provide sources where to learn more on that subject. if in doubt, do not answer with code and ask to clarify the prompt!',
            'active' => true,
            'user_id' => $user->id,
            'module_id' => $module->id,
        ]);

        $collection = Collections::query()
            ->firstOrCreate([
            'name' => 'DemoCollection'
            ], [
                'max_results' => 5,
                'module_id' => $module->id,
            ]);

        if ($collection->wasRecentlyCreated) {
            $result = ChromaController::createCollection($collection->name);

            if (!$result['status']) {
                $collection->forceDelete();
                $user->delete();
                $module->delete();
                $agent->delete();
                abort(500, $result['message']);
            }
        }
    }
}
