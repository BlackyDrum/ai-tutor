<?php

namespace App\Console\Commands;

use App\Http\Controllers\ChromaController;
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

        $client = ChromaController::getClient();

        $this->info("ChromaDB Version: {$client->version()}");
        $this->info("ChromaDB Database: {$client->database}");
        $this->info("ChromaDB Tenant: {$client->tenant}\n");

        $collections = $client->listCollections();
        $chromaCollectionCount = count($collections);

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
        foreach ($collections as $collection) {
            $relationalCollection = Collection::query()
                ->where('name', '=', $collection->name)
                ->first();

            if (!$relationalCollection) {
                $this->error(
                    "Cannot find RelationalDB Collection for {$collection->name}"
                );

                $this->error($failMessage);

                return -1;
            }

            if (
                $relationalCollection->max_results !=
                $collection->metadata['max_results']
            ) {
                $this->error(
                    "'Max Results' doesn't match for collection {$collection->name}. RelationalDB: {$relationalCollection->max_results}, ChromaDB: {$collection->metadata['max_results']}"
                );

                $error = true;
            }

            $names[] = $collection->name;
        }

        // Check if all RelationalDB collections have a corresponding
        // collection in ChromaDB
        foreach ($relationalCollections as $collection) {
            try {
                ChromaController::getCollection($collection->name);
            } catch (\Exception $exception) {
                $this->error(
                    "Cannot find ChromaDB Collection for {$collection->name}"
                );

                $this->error($failMessage);

                return -1;
            }
        }

        // At this stage, we've confirmed that the collections in both databases are identical
        foreach ($names as $collectionName) {
            $collectionError = false;

            $this->info("Validating collection $collectionName...");

            $collection = ChromaController::getCollection($collectionName);

            $collectionId = Collection::query()
                ->where('name', '=', $collectionName)
                ->first()->id;

            $relationalDB = Embedding::query()
                ->where('collection_id', '=', $collectionId)
                ->get();

            $relationalDBCount = $relationalDB->count();

            if ($collection->count() != $relationalDBCount) {
                $this->error(
                    "Count of embeddings doesn't match: RelationalDB: $relationalDBCount, ChromaDB: {$collection->count()}"
                );
                $error = true;
                $collectionError = true;
            } else {
                $this->info(
                    "Count of embeddings matches: RelationalDB: $relationalDBCount, ChromaDB: {$collection->count()}"
                );
            }

            $this->info("Validating embeddings for $collectionName...");

            // Check if all embeddings in our relational database
            // have a corresponding embedding in ChromaDB. If found,
            // we additionally check the metadata, e.g size, content...
            foreach ($relationalDB as $relationalEmbedding) {
                $embedding = $collection->get(
                    ids: [$relationalEmbedding->embedding_id],
                    include: ['documents', 'metadatas']
                );

                if (!$embedding->ids) {
                    $this->error(
                        "Cannot find ChromaDB Embedding for {$relationalEmbedding->embedding_id}"
                    );
                    $error = true;
                    $collectionError = true;
                    continue;
                }

                if ($embedding->documents[0] != $relationalEmbedding->content) {
                    $this->error(
                        "Content of {$relationalEmbedding->embedding_id} doesn't match."
                    );
                    $error = true;
                    $collectionError = true;
                }

                $name = $embedding->metadatas[0]['name'];
                if ($name != $relationalEmbedding->name) {
                    $this->error(
                        "Name of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB: {$relationalEmbedding->name}, ChromaDB: $name"
                    );
                    $error = true;
                    $collectionError = true;
                }

                $size = $embedding->metadatas[0]['size'];
                if ($size != $relationalEmbedding->size) {
                    $this->error(
                        "Size of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB: {$relationalEmbedding->size}, ChromaDB: $size"
                    );
                    $error = true;
                    $collectionError = true;
                }

                $documentName = $embedding->metadatas[0]['document'];
                try {
                    $document = Document::query()->findOrFail(
                        $relationalEmbedding->document_id
                    );
                    if ($documentName != $document->name) {
                        $this->error(
                            "Document of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB: {$document->name}, ChromaDB: $documentName"
                        );
                        $error = true;
                        $collectionError = true;
                    } elseif (
                        $document->collection_id !=
                        $relationalEmbedding->collection_id
                    ) {
                        $this->error(
                            "Document Name matches, but collection doesn't match for {$relationalEmbedding->embedding_id}. Document Collection ID: {$document->collection_id}, Embedding Collection ID: {$relationalEmbedding->collection_id}"
                        );
                        $error = true;
                        $collectionError = true;
                    }
                } catch (ModelNotFoundException $exception) {
                    $this->error(
                        "Cannot find relational document for {$relationalEmbedding->embedding_id}. Document: {$documentName}"
                    );
                    $error = true;
                    $collectionError = true;
                }
            }

            $this->info(
                "Additionally checking ChromaDB for $collectionName..."
            );

            $embeddings = $collection->get();

            // Check if all embeddings in ChromaDB have a corresponding
            // embedding in the relational database. We just need to check
            // they exist because we've already confirmed the metadata matches
            // if they exist in both databases
            foreach ($embeddings->ids as $embedding) {
                $e = Embedding::query()
                    ->where('embedding_id', '=', $embedding)
                    ->first();

                if (!$e) {
                    $this->error(
                        "Cannot find RelationalDB Embedding for {$embedding}"
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

        return 1;
    }
}
