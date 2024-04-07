<?php

namespace App\Console\Commands;

use App\Classes\ChromaDB;
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
    protected $signature = 'chroma:sync {--source=}';

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
        $warnMessage =
            "Please specify the authoritative data source by using '--source=chroma' for ChromaDB or '--source=relational' for the relational database. The selected source's data will be replicated to the other database, and existing data in the target database will be overwritten or removed.";
        $option = $this->option('source');

        if ($option != 'chroma' && $option != 'relational') {
            $this->warn($warnMessage);
            return -1;
        }

        $this->info('Syncing...');

        $client = ChromaDB::getClient();

        if ($option == 'relational') {
            $relationalCollections = Collection::all();

            $client->deleteAllCollections();

            foreach ($relationalCollections as $relationalCollection) {
                ChromaDB::createCollection($relationalCollection);

                $chromaCollection = ChromaDB::getCollection(
                    $relationalCollection->name
                );

                $relationalEmbeddings = Embedding::query()
                    ->where('collection_id', '=', $relationalCollection->id)
                    ->get();

                $ids = [];
                $metadatas = [];
                $documents = [];
                foreach ($relationalEmbeddings as $relationalEmbedding) {
                    $document = Document::query()->find(
                        $relationalEmbedding->document_id
                    );
                    $metadata = [
                        'name' => $relationalEmbedding->name,
                        'size' => $relationalEmbedding->size,
                        'document' => $document->name,
                        'document_md5' => $document->md5,
                    ];

                    $ids[] = $relationalEmbedding->embedding_id;
                    $documents[] = $relationalEmbedding->content;
                    $metadatas[] = $metadata;
                }

                if (!empty($ids)) {
                    $chromaCollection->add(
                        ids: $ids,
                        metadatas: $metadatas,
                        documents: $documents
                    );
                }
            }
        } else {
            $chromaCollections = $client->listCollections();

            $collectionNames = [];

            foreach ($chromaCollections as $chromaCollection) {
                $collection = Collection::query()->updateOrCreate(
                    [
                        'name' => $chromaCollection->name,
                    ],
                    [
                        'max_results' =>
                            $chromaCollection->metadata['max_results'],
                    ]
                );

                $collectionNames[] = $collection->name;
            }

            Collection::query()
                ->whereNotIn('name', $collectionNames)
                ->forceDelete();

            $relationalCollections = Collection::all();

            foreach ($relationalCollections as $relationalCollection) {
                $chromaCollection = ChromaDB::getCollection(
                    $relationalCollection->name
                );

                $data = $chromaCollection->get(
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
                        'md5' => $metadata[$key]['document_md5'],
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
        }

        $this->info(
            'Synced ChromaDB with relational database. Run \'php artisan chroma:check\' to validate.'
        );

        return 0;
    }
}
