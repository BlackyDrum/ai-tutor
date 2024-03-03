<?php

namespace App\Console\Commands;

use App\Http\Controllers\ChromaController;
use App\Models\Collections;
use App\Models\Files;
use Illuminate\Console\Command;

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

        $client = ChromaController::getClient();

        $this->info("ChromaDB Version: {$client->version()}");
        $this->info("ChromaDB Database: {$client->database}");
        $this->info("ChromaDB Tenant: {$client->tenant}\n");

        $collections = $client->listCollections();

        $names = [];

        foreach ($collections as $collection) {
            $names[] = $collection->name;
        }

        $error = false;

        foreach ($names as $collectionName) {
            $collectionError = false;

            $this->info("Validating collection $collectionName...");

            $collection = ChromaController::getCollection($collectionName);

            $collectionId = Collections::query()
                ->where('name', '=', $collectionName)
                ->first()
                ->id;

            $relationalDB = Files::query()
                ->where('collection_id', '=', $collectionId)
                ->whereNotNull('content')
                ->get();

            $relationalDBCount = $relationalDB->count();

            if ($collection->count() != $relationalDBCount) {
                $this->error("Count of embeddings doesn't match: RelationalDB: $relationalDBCount, ChromaDB: {$collection->count()}");
                $error = true;
                $collectionError = true;
            }
            else {
                $this->info("Count of embeddings matches: RelationalDB: $relationalDBCount, ChromaDB: {$collection->count()}");
            }

            $this->info("Validating embeddings for $collectionName...");
            foreach ($relationalDB as $relationalEmbedding) {
                $embedding = $collection->get(
                    ids: [$relationalEmbedding->embedding_id],
                    include: ['documents', 'metadatas']
                );

                if (!$embedding->ids) {
                    $this->error("Cannot find ChromaDB Embedding for {$relationalEmbedding->embedding_id}");
                    $error = true;
                    $collectionError = true;
                    continue;
                }

                if ($embedding->documents[0] != $relationalEmbedding->content) {
                    $this->error("Content of {$relationalEmbedding->embedding_id} doesn't match.");
                    $error = true;
                    $collectionError = true;
                }

                $name = $embedding->metadatas[0]['filename'];
                if ($name != $relationalEmbedding->name) {
                    $this->error("Name of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB Name: $name, ChromaDB Name: {$relationalEmbedding->name}");
                    $error = true;
                    $collectionError = true;
                }

                $size = $embedding->metadatas[0]['size'];
                if ($size != $relationalEmbedding->size) {
                    $this->error("Size of {$relationalEmbedding->embedding_id} doesn't match. RelationalDB Name: $size, ChromaDB Name: {$relationalEmbedding->size}");
                    $error = true;
                    $collectionError = true;
                }
            }

            $this->info("Additionally checking ChromaDB for $collectionName...");

            $embeddings = $collection->get();

            foreach ($embeddings->ids as $embedding) {
                $f = Files::query()
                    ->where('embedding_id', '=', $embedding)
                    ->first();

                if (!$f) {
                    $this->error("Cannot find RelationalDB Embedding for {$embedding}");
                    $error = true;
                    $collectionError = true;
                }
            }

            if (!$collectionError) {
                $this->info("Collection $collectionName is in sync \u{2713}\n");
            }
            else {
                $this->error("Check for collection $collectionName failed\n");
            }
        }

        if (!$error) {
            $this->info("All tests passed. RelationalDB is in sync with ChromaDB \u{2713}");
        }
        else {
            $this->error("RelationalDB is NOT in sync with ChromaDB");

            return -1;
        }

        return 1;
    }
}
