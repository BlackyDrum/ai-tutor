<?php

namespace App\Console\Commands;

use App\Classes\ChromaDB;
use App\Models\Collection;
use App\Models\Document;
use App\Models\Embedding;
use Illuminate\Console\Command;

class DeleteCollections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chroma:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all stored data related to ChromaDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Deleting all collections and embeddings...');

        $chromaDB = ChromaDB::getClient();

        $chromaDB->deleteAllCollections();

        Collection::query()->forceDelete();

        Embedding::query()->forceDelete();

        Document::query()->delete();
    }
}
