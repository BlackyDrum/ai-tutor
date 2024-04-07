<?php

namespace App\Console\Commands;

use App\Classes\ChromaDB;
use App\Models\Collection;
use App\Models\Document;
use App\Models\Embedding;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ValidateChromaDBSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chroma:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validates if ChromaDB is in sync with our relational database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting ChromaDB sync validation...\n");

        $error = false;

        $failMessage =
            "Relational Database is NOT in sync with ChromaDB.\nConsider running 'php artisan chroma:sync' to sync the databases.";

        $client = ChromaDB::getClient();

        $this->info("ChromaDB Version: {$client->version()}");
        $this->info("ChromaDB Database: {$client->database}");
        $this->info("ChromaDB Tenant: {$client->tenant}\n");

        $chromaCollections = $client->listCollections();
        $chromaCollectionCount = count($chromaCollections);

        $relationalCollections = Collection::all();

        $this->info('Validating collections...');

        if ($relationalCollections->count() != $chromaCollectionCount) {
            $this->error(
                "Count of collections doesn't match: RelationalDB: {$relationalCollections->count()}, ChromaDB: $chromaCollectionCount\n"
            );
        } else {
            $this->info(
                "Count of collections matches: RelationalDB: {$relationalCollections->count()}, ChromaDB: $chromaCollectionCount \u{2713}\n"
            );
        }

        $names = [];

        // Check if all ChromaDB collections have a corresponding
        // collection in the relational database
        foreach ($chromaCollections as $chromaCollection) {
            $relationalCollection = Collection::query()
                ->where('name', '=', $chromaCollection->name)
                ->first();

            if (!$relationalCollection) {
                $this->error(
                    "Cannot find RelationalDB Collection for {$chromaCollection->name}"
                );

                $this->error($failMessage);

                return -1;
            }

            if (
                $relationalCollection->max_results !=
                $chromaCollection->metadata['max_results']
            ) {
                $this->error(
                    "'Max Results' doesn't match for collection {$chromaCollection->name}. RelationalDB: {$relationalCollection->max_results}, ChromaDB: {$chromaCollection->metadata['max_results']}"
                );

                $error = true;
            }

            $names[] = $chromaCollection->name;
        }

        // Check if all RelationalDB collections have a corresponding
        // collection in ChromaDB
        foreach ($relationalCollections as $relationalCollection) {
            try {
                ChromaDB::getCollection($relationalCollection->name);
            } catch (\Exception $exception) {
                $this->error(
                    "Cannot find ChromaDB Collection for {$relationalCollection->name}"
                );

                $this->error($failMessage);

                return -1;
            }
        }

        // At this stage, we've confirmed that the collections in both databases are identical
        foreach ($names as $collectionName) {
            $collectionError = false;

            $this->info("Validating collection $collectionName...");

            $chromaCollection = ChromaDB::getCollection(
                $collectionName
            );

            $collectionId = Collection::query()
                ->where('name', '=', $collectionName)
                ->first()->id;

            $relationalEmbeddings = Embedding::query()
                ->where('collection_id', '=', $collectionId)
                ->get();

            $relationalDBCount = $relationalEmbeddings->count();

            if ($chromaCollection->count() != $relationalDBCount) {
                $this->error(
                    "Count of embeddings doesn't match: RelationalDB: $relationalDBCount, ChromaDB: {$chromaCollection->count()}"
                );
                $error = true;
                $collectionError = true;
            } else {
                $this->info(
                    "Count of embeddings matches: RelationalDB: $relationalDBCount, ChromaDB: {$chromaCollection->count()}"
                );
            }

            $this->info(
                'Checking if all embeddings in the relational database have a corresponding embedding in ChromaDB...'
            );

            // Check if all embeddings in our relational database
            // have a corresponding embedding in ChromaDB. If found,
            // we additionally check the metadata, e.g size, content...
            foreach ($relationalEmbeddings as $relationalEmbedding) {
                $chromaEmbedding = $chromaCollection->get(
                    ids: [$relationalEmbedding->embedding_id],
                    include: ['documents', 'metadatas']
                );

                if (!$chromaEmbedding->ids) {
                    $this->error(
                        "Cannot find ChromaDB Embedding for {$relationalEmbedding->embedding_id}"
                    );
                    $error = true;
                    $collectionError = true;
                    continue;
                }

                if (
                    $chromaEmbedding->documents[0] !=
                    $relationalEmbedding->content
                ) {
                    $this->error(
                        "Content of {$relationalEmbedding->embedding_id} doesn't match."
                    );
                    $error = true;
                    $collectionError = true;
                }

                $chromaEmbeddingName = $chromaEmbedding->metadatas[0]['name'];
                if ($chromaEmbeddingName != $relationalEmbedding->name) {
                    $this->error(
                        "Name of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB: {$relationalEmbedding->name}, ChromaDB: $chromaEmbeddingName"
                    );
                    $error = true;
                    $collectionError = true;
                }

                $chromaEmbeddingSize = $chromaEmbedding->metadatas[0]['size'];
                if ($chromaEmbeddingSize != $relationalEmbedding->size) {
                    $this->error(
                        "Size of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB: {$relationalEmbedding->size}, ChromaDB: $chromaEmbeddingSize"
                    );
                    $error = true;
                    $collectionError = true;
                }

                $chromaEmbeddingDocumentName =
                    $chromaEmbedding->metadatas[0]['document'];
                try {
                    $relationalDocument = Document::query()->findOrFail(
                        $relationalEmbedding->document_id
                    );
                    if (
                        $chromaEmbeddingDocumentName !=
                        $relationalDocument->name
                    ) {
                        $this->error(
                            "Document Name of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB: {$relationalDocument->name}, ChromaDB: $chromaEmbeddingDocumentName"
                        );
                        $error = true;
                        $collectionError = true;
                    } elseif (
                        $relationalDocument->collection_id !=
                        $relationalEmbedding->collection_id
                    ) {
                        $this->error(
                            "Document Name matches, but collection doesn't match for {$relationalEmbedding->embedding_id}. Document Collection ID: {$relationalDocument->collection_id}, Embedding Collection ID: {$relationalEmbedding->collection_id}"
                        );
                        $error = true;
                        $collectionError = true;
                    }

                    $chromaEmbeddingDocumentMD5 =
                        $chromaEmbedding->metadatas[0]['document_md5'];
                    if (
                        $chromaEmbeddingDocumentMD5 != $relationalDocument->md5
                    ) {
                        $this->error(
                            "Document Hash of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB: {$relationalDocument->md5}, ChromaDB: $chromaEmbeddingDocumentMD5"
                        );
                        $error = true;
                        $collectionError = true;
                    }
                } catch (ModelNotFoundException $exception) {
                    $this->error(
                        "Cannot find relational document for {$relationalEmbedding->embedding_id}. Document: {$chromaEmbeddingDocumentName}"
                    );
                    $error = true;
                    $collectionError = true;
                }
            }

            $this->info(
                'Additionally checking if all embeddings in ChromaDB have a corresponding embedding in the relational database...'
            );

            $chromaEmbeddings = $chromaCollection->get();

            // Check if all embeddings in ChromaDB have a corresponding
            // embedding in the relational database. We just need to check
            // they exist because we've already confirmed the metadata matches
            // if they exist in both databases
            foreach ($chromaEmbeddings->ids as $chromaEmbeddingId) {
                $relationalEmbedding = Embedding::query()
                    ->where('embedding_id', '=', $chromaEmbeddingId)
                    ->first();

                if (!$relationalEmbedding) {
                    $this->error(
                        "Cannot find RelationalDB Embedding for {$chromaEmbeddingId}"
                    );
                    $error = true;
                    $collectionError = true;
                }
            }

            if (!$collectionError) {
                $this->info("Collection $collectionName is in sync \u{2713}\n");
            } else {
                $this->error("Check for collection $collectionName failed\n");
            }
        }

        if (!$error) {
            $this->info(
                "All tests passed. Relational Database is in sync with ChromaDB \u{2713}"
            );
        } else {
            $this->error($failMessage);

            return -1;
        }

        return 0;
    }
}
