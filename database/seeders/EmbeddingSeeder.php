<?php

namespace Database\Seeders;

use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Embeddings\JinaEmbeddingFunction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmbeddingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chromaDB = ChromaDB::client();

        $embeddingFunction = new JinaEmbeddingFunction(config('api.jina_api_key'));

        $chromaDB->createCollection(config('api.collection_name'), embeddingFunction: $embeddingFunction);
    }
}
