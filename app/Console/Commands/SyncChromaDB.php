<?php

namespace App\Console\Commands;

use App\Http\Controllers\ChromaController;
use App\Models\Collection;
use App\Models\Document;
use App\Models\Embedding;
use Illuminate\Console\Command;

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
            $collection = Collection::query()->updateOrCreate(
                [
                    'name' => $chromaCollection->name,
                ],
                [
                    'max_results' => $chromaCollection->metadata['max_results'],
                ]
            );

            $collectionNames[] = $collection->name;
        }

        Collection::query()
            ->whereNotIn('name', $collectionNames)
            ->forceDelete();

        $relationalCollections = Collection::all();

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

            $documentIds = [];

            foreach ($ids as $key => $id) {
                $document = Document::query()->firstOrCreate([
                    'name' => $metadata[$key]['document'],
                    'collection_id' => $relationalCollection->id,
                ]);

                $documentIds[] = $document->id;

                $embedding = Embedding::query()->updateOrCreate(
                    [
                        'embedding_id' => $id,
                    ],
                    [
                        'name' => $metadata[$key]['name'],
                        'content' => $documents[$key],
                        'size' => $metadata[$key]['size'],
                        'collection_id' => $relationalCollection->id,
                        'document_id' => $document->id,
                    ]
                );

                $embeddingIds[] = $embedding->embedding_id;
            }

            Document::query()
                ->where('collection_id', '=', $relationalCollection->id)
                ->whereNotIn('id', array_unique($documentIds))
                ->delete();

            Embedding::query()
                ->where('collection_id', '=', $relationalCollection->id)
                ->whereNotIn('embedding_id', $embeddingIds)
                ->forceDelete();
        }

        $this->info(
            'Synced ChromaDB with relational database. Run \'php artisan chroma:check\' to validate.'
        );
    }
}
