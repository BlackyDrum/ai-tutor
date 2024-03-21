<?php

namespace App\Console\Commands;

use App\Http\Controllers\ChromaController;
use App\Models\Collections;
use App\Models\Files;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncChromaDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chroma:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs ChromaDB with our relational database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing...');

        $client = ChromaController::getClient();

        $chromaCollections = $client->listCollections();

        $collectionNames = [];

        foreach ($chromaCollections as $chromaCollection) {
            $collection = Collections::query()->firstOrCreate(
                [
                    'name' => $chromaCollection->name,
                ],
                [
                    'max_results' => $chromaCollection->metadata['max_results'],
                    'module_id' => null,
                ]
            );

            $collectionNames[] = $collection->name;
        }

        Collections::query()
            ->whereNotIn('name', $collectionNames)
            ->forceDelete();

        $relationalCollections = Collections::all();

        foreach ($relationalCollections as $relationalCollection) {
            $collection = ChromaController::getCollection(
                $relationalCollection->name
            );

            $data = $collection->get(
                include: ['embeddings', 'metadatas', 'documents']
            );

            $documents = $data->documents;
            $metadata = $data->metadatas;
            $ids = $data->ids;

            $embeddingIds = [];

            foreach ($ids as $key => $id) {
                $file = Files::query()->firstOrCreate(
                    [
                        'embedding_id' => $id,
                    ],
                    [
                        'name' => $metadata[$key]['filename'],
                        'content' => $documents[$key],
                        'size' => $metadata[$key]['size'],
                        'user_id' => null,
                        'collection_id' => $relationalCollection->id,
                    ]
                );

                $embeddingIds[] = $file->embedding_id;
            }

            Files::query()
                ->where('collection_id', '=', $relationalCollection->id)
                ->whereNotIn('embedding_id', $embeddingIds)
                ->forceDelete();
        }

        $this->info(
            'Synced ChromaDB with relational database. Run \'php artisan chroma:check\' to validate.'
        );
    }
}
