<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collections;
use App\Models\Files;
use Smalot\PdfParser\Parser;
use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Embeddings\JinaEmbeddingFunction;

class ChromaController extends Controller
{
    public static function createEmbedding($model)
    {
        $model->embedding_id = substr($model->path, strrpos($model->path, '/') + 1);

        $pathToFile = storage_path() . '/app/' . $model->path;

        $filename = $model->name;

        if (str_ends_with($filename, 'pdf')) {
            $parser = new Parser();

            try {
                $pdf = $parser->parseFile($pathToFile);
            } catch (\Exception $exception) {

                if (file_exists($pathToFile)) {
                    unlink($pathToFile);
                }

                return false;
            }

            $text = $pdf->getText();
        }
        else {
            $text = file_get_contents($pathToFile);
        }


        try {
            $collection = Collections::query()->find($model->collection_id)->name;

            $collection = self::getCollection($collection);

            $id = [$model->embedding_id];
            $document = [$text];
            $metadata = [
                [
                    'filename' => $model->name,
                    'size' => $model->size,
                ]
            ];

            $collection->add(
                ids: $id,
                metadatas: $metadata,
                documents: $document
            );

        } catch (\Exception $exception) {
            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            return false;
        }

        $model->save();

        return true;
    }

    public static function deleteEmbedding($model)
    {
        $collection = Collections::query()->find($model->collection_id)->name;

        try {
            $collection = self::getCollection($collection);

            $collection->delete([$model->embedding_id]);

            $pathToFile = storage_path() . '/app/' . $model->path;

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    public static function createCollection($name)
    {
        try {
            $chromaDB = self::getClient();

            $embeddingFunction = self::getEmbeddingFunction();

            $chromaDB->createCollection($name, embeddingFunction: $embeddingFunction);
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    public static function deleteCollection($model)
    {
        $files = Files::query()->where('collection_id', '=', $model->id)->get();

        try {
            $chromaDB = self::getClient();

            $chromaDB->deleteCollection($model->name);
        } catch (\Exception $exception) {
            return false;
        }

        foreach ($files as $file) {
            $pathToFile = storage_path() . '/app/' . $file->path;

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }
        }

        return true;
    }

    public static function getCollection($collection)
    {
        $chromaDB = self::getClient();

        $embeddingFunction = self::getEmbeddingFunction();

        return $chromaDB->getCollection($collection, embeddingFunction: $embeddingFunction);
    }

    public static function getEmbeddingFunction()
    {
        return new JinaEmbeddingFunction(config('api.jina_api_key'));
    }

    public static function getClient()
    {
        return ChromaDB::factory()
            ->withHost(config('api.chroma_host'))
            ->withPort(config('api.chroma_port'))
            ->withDatabase(config('api.chroma_database'))
            ->withTenant(config('api.chroma_tenant'))
            ->connect();
    }
}
