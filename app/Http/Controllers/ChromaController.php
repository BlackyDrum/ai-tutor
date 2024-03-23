<?php

namespace App\Http\Controllers;

use App\Models\Collections;
use App\Models\ConversationHasDocument;
use App\Models\Conversations;
use App\Models\Files;
use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Embeddings\JinaEmbeddingFunction;
use Codewithkyrian\ChromaDB\Embeddings\OpenAIEmbeddingFunction;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChromaController extends Controller
{
    public static function createPromptWithContext(
        $collectionName,
        $message,
        $conversation_id
    ) {
        $collection = self::getCollection($collectionName);

        $maxResults = Collections::query()
            ->where('name', '=', $collectionName)
            ->first()->max_results;

        $queryResponse = $collection->query(
            queryTexts: [$message],
            nResults: $maxResults
        );

        $enhancedMessage = "\nUser Message:\n" . $message . "\n\n";

        $conversation = Conversations::query()
            ->where('url_id', '=', $conversation_id)
            ->first();

        foreach ($queryResponse->ids[0] as $id) {
            $file = Files::query()->where('embedding_id', '=', $id)->first();

            $count = ConversationHasDocument::query()
                ->where('conversation_id', '=', $conversation->id)
                ->where('file_id', '=', $file->id)
                ->count();

            // If document is already embedded in context
            if ($count > 0) {
                continue;
            }

            ConversationHasDocument::query()->create([
                'conversation_id' => $conversation->id,
                'file_id' => $file->id,
            ]);

            $enhancedMessage .= "\n\"\"\"\n";
            $enhancedMessage .= "Context Document:\n" . $file->content . "\n";
            $enhancedMessage .= "\"\"\"\n";
        }

        return $enhancedMessage;
    }

    public static function createEmbedding($model)
    {
        $pathToFile = storage_path() . '/app/' . $model->embedding_id;

        $filename = $model->name;

        $collectionId = $model->collection_id;

        // We need to manually create the files here, because the API endpoint
        // returns small artifacts of the pptx file. We do not want to store
        // the whole pptx file, but rather these small artifacts. Each artifact
        // represents a slide, and each slide represents an embedding.
        if (str_ends_with($filename, 'pptx')) {
            try {
                $token = HomeController::getBearerToken();
            } catch (\Exception $exception) {
                if (file_exists($pathToFile)) {
                    unlink($pathToFile);
                }

                throw $exception;
            }

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->asMultipart()
                ->post(config('conversaition.url') . '/data/pptx-to-md', [
                    [
                        'name' => 'pptxfile',
                        'contents' => fopen($pathToFile, 'r'),
                        'headers' => [
                            'Content-Type' => 'application/octet-stream',
                        ],
                    ],
                ]);

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            if ($response->failed()) {
                throw new \Exception('Failed to convert pptx to json');
            }

            $result = self::createEmbeddingFromJson($response->json(), $model);

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        } elseif (str_ends_with($filename, 'json')) {
            $json = json_decode(file_get_contents($pathToFile), true);

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            $result = self::createEmbeddingFromJson($json, $model);

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        } elseif (str_ends_with($filename, 'txt')) {
            $text = file_get_contents($pathToFile);

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            $model->content = $text ?? '';

            $ids = [$model->embedding_id];
            $documents = [$text];
            $metadata = [
                [
                    'filename' => $model->name,
                    'size' => $model->size,
                ],
            ];

            $model->user_id = Auth::id();

            $model->save();
        } elseif (str_ends_with($filename, 'md')) {
            $markdown = file_get_contents($pathToFile);

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            $result = self::createEmbeddingFromMarkdown($markdown, $model);

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        } else {
            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            throw new \Exception(
                'Attempted to process a file with the wrong format'
            );
        }

        $collection = Collections::query()->find($collectionId)->name;

        $collection = self::getCollection($collection);

        $collection->add(
            ids: $ids,
            metadatas: $metadata,
            documents: $documents
        );
    }

    private static function createAndStoreSlide($model, $title, $body, $index)
    {
        $embedding_id = Str::random(40);
        $contentOnSlide = "Title: $title\n$body";

        Files::query()->create([
            'embedding_id' => $embedding_id,
            'name' => $model->name . " Slide $index",
            'content' => $contentOnSlide,
            'size' => strlen($contentOnSlide),
            'user_id' => Auth::id(),
            'collection_id' => $model->collection_id,
        ]);

        $metadata = [
            'filename' => $model->name . " Slide $index",
            'size' => strlen($contentOnSlide),
        ];

        return [
            'id' => $embedding_id,
            'document' => $contentOnSlide,
            'metadata' => $metadata,
        ];
    }

    private static function createEmbeddingFromMarkdown($markdown, $model)
    {
        $markdown = preg_replace('/---/s', '', $markdown);

        $slides = preg_split('/\n# /', $markdown, -1, PREG_SPLIT_NO_EMPTY);

        $ids = [];
        $documents = [];
        $metadata = [];
        $index = 1;

        foreach ($slides as $key => $slide) {
            // Since we're splitting by '\n# ', all slides except the first will not have '# ' in front. Add it manually.
            if ($key > 0) {
                $slide = '# ' . $slide;
            }

            $slide = array_values(
                array_filter(
                    explode("\n", $slide),
                    fn($element) => !empty(trim($element))
                )
            );

            $result = self::createAndStoreSlide(
                $model,
                substr($slide[0], 1), // title
                $slide[1], // content
                $index
            );

            $ids[] = $result['id'];
            $documents[] = $result['document'];
            $metadata[] = $result['metadata'];

            $index++;
        }

        return [
            'ids' => $ids,
            'documents' => $documents,
            'metadata' => $metadata,
        ];
    }

    private static function createEmbeddingFromJson($json, $model)
    {
        $ids = [];
        $documents = [];
        $metadata = [];
        $index = 1;

        foreach ($json['content'] as $content) {
            $title = $content['title'];
            $body = implode("\n", $content['content']);

            $result = self::createAndStoreSlide($model, $title, $body, $index);

            $ids[] = $result['id'];
            $documents[] = $result['document'];
            $metadata[] = $result['metadata'];

            $index++;
        }

        return [
            'ids' => $ids,
            'documents' => $documents,
            'metadata' => $metadata,
        ];
    }

    public static function updateEmbedding($model)
    {
        $collection = Collections::query()->find($model->collection_id)->name;

        $collection = self::getCollection($collection);

        $collection->update(
            ids: [$model->embedding_id],
            metadatas: [
                [
                    'filename' => $model->name,
                    'size' => strlen($model->content),
                ],
            ],
            documents: [$model->content]
        );

        $model->size = strlen($model->content);

        $model->save();
    }

    public static function deleteEmbedding($model)
    {
        $collection = Collections::query()->find($model->collection_id)->name;

        $collection = self::getCollection($collection);

        $embedding = $collection->get(ids: [$model->embedding_id]);

        // We need to throw an exception here by ourselves, because the
        // ChromaDB PHP adapter we are using doesn't do that
        if (!$embedding->ids) {
            throw new \Exception(
                "Trying to delete non-existing embedding: {$model->embedding_id}"
            );
        }

        $collection->delete([$model->embedding_id]);
    }

    public static function createCollection($model)
    {
        $chromaDB = self::getClient();

        $embeddingFunction = self::getEmbeddingFunction();

        $metadata = ['max_results' => $model->max_results];

        $chromaDB->createCollection(
            $model->name,
            $metadata,
            embeddingFunction: $embeddingFunction
        );
    }

    public static function updateCollection($oldName, $model)
    {
        $collection = self::getCollection($oldName);

        $metadata = ['max_results' => $model->max_results];

        $collection->modify($model->name, $metadata);
    }

    public static function replicateCollection($original, $copy)
    {
        $files = Files::query()
            ->where('collection_id', '=', $original->id)
            ->get();

        self::createCollection($copy);

        $originalCollection = self::getCollection($original->name);

        $ids = [];
        $embeddings = [];
        $metadata = [];
        $documents = [];

        foreach ($files as $file) {
            $replicate = $file->replicate(['created_at', 'updated_at'])->fill([
                'embedding_id' => Str::random(40),
                'collection_id' => $copy->id,
            ]);

            $ids[] = $replicate->embedding_id;
            $metadata[] = [
                'filename' => $replicate->name,
                'size' => strlen($replicate->content),
            ];
            $documents[] = $replicate->content;

            $replicate->save();

            $embedding = $originalCollection->get(ids: [$file->embedding_id]);

            $embeddings[] = $embedding->embeddings[0];
        }

        $collection = self::getCollection($copy->name);

        $collection->add(
            ids: $ids,
            embeddings: $embeddings,
            metadatas: $metadata,
            documents: $documents
        );
    }

    public static function deleteCollection($model)
    {
        $chromaDB = self::getClient();

        $chromaDB->deleteCollection($model->name);
    }

    public static function getCollection($collection)
    {
        $chromaDB = self::getClient();

        $embeddingFunction = self::getEmbeddingFunction();

        return $chromaDB->getCollection(
            $collection,
            embeddingFunction: $embeddingFunction
        );
    }

    public static function getEmbeddingFunction()
    {
        $embeddingFunction = config('chromadb.embedding_function');

        if ($embeddingFunction == 'openai') {
            return new OpenAIEmbeddingFunction(
                config('api.openai_api_key'),
                '',
                config('api.openai_embedding_model')
            );
        }

        return new JinaEmbeddingFunction(
            config('api.jina_api_key'),
            config('api.jina_embedding_model')
        );
    }

    public static function getClient()
    {
        return ChromaDB::factory()
            ->withHost(config('chromadb.host'))
            ->withPort(config('chromadb.port'))
            ->withDatabase(config('chromadb.database'))
            ->withTenant(config('chromadb.tenant'))
            ->withAuthToken(config('chromadb.server_auth_credentials'))
            ->connect();
    }
}
