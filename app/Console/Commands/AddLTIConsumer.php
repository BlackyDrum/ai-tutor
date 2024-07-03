<?php

namespace App\Console\Commands;

use App\Classes\ChromaDB;
use App\Models\Agent;
use App\Models\Collection;
use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AddLTIConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lti:add_consumer {name} {consumer_key} {shared_secret} {ref_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integrate a new platform using LTI 1.0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $consumerKey = $this->argument('consumer_key');
        $sharedSecret = $this->argument('shared_secret');
        $refId = $this->argument('ref_id');

        $validator = Validator::make([
            'name' => $name,
            'consumer_key' => $consumerKey,
            'ref_id' => $refId
        ], [
            'name' => 'string|unique:lti2_consumer,name',
            'consumer_key' => 'string|unique:lti2_consumer,consumer_key',
            'ref_id' => 'integer|unique:modules,ref_id'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);

                Log::error('Failed to add LTI Consumer. Reason: ' . $error);
            }
            return -1;
        }

        DB::beginTransaction();

        $module = new Module();
        $module->name = $name;
        $module->ref_id = $refId;
        $module->save();

        $collectionName = "Collection_" . Collection::query()->max('id') + 1;
        $collection = new Collection();
        $collection->name = $collectionName;
        $collection->max_results = 3;
        $collection->active = true;
        $collection->module_id = $module->id;
        $collection->save();

        try {
            ChromaDB::createCollection($collection);
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());

            Log::error('Failed to add LTI Consumer. Reason: ' . $exception->getMessage());

            DB::rollBack();

            return -1;
        }

        $agentName = "Agent #" . Agent::query()->max('id') + 1;
        $agent = new Agent();
        $agent->name = $agentName;
        $agent->instructions = 'You are a helpful tutor';
        $agent->openai_language_model = 'gpt-4o';
        $agent->max_messages_included = 12;
        $agent->temperature = 0.7;
        $agent->max_response_tokens = 1000;
        $agent->active = true;
        $agent->module_id = $module->id;
        $agent->save();

        Artisan::call("lti:add_platform_1.2 \"$name\" \"$consumerKey\" \"$sharedSecret\"");

        DB::commit();

        $this->info("A new LTI 1.0 platform has been successfully created. A default agent named '$agentName' and a collection named '$collectionName' have been created. Please update these values as needed in the dashboard.");

        return 0;
    }
}
