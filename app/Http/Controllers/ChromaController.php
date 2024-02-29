<?php

namespace App\Http\Controllers;

use App\Models\Collections;
use App\Models\ConversationHasDocument;
use App\Models\Conversations;
use App\Models\Files;
use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Embeddings\JinaEmbeddingFunction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChromaController extends Controller
{
    public static function createPromptWithContext($collectionName, $message, $conversation_id, $pastMessages = null)
    {
        $collection = self::getCollection($collectionName);

        $queryResponse = $collection->query(
            queryTexts: [
                $message
            ],
            nResults: config('chromadb.max_document_results')
        );

        $enhancedMessage = "Try to answer the following user message. Always try to answer in the language from the user's message.\n" .
                           "You will also find the user messages from the past. If the current message doesn't make sense" .
                           "always address the previous user messages\n" .
                           "Below you will find some context documents (delimited by Hashtags) that may help. Ignore it if it seems irrelevant.\n\n";
                           //"Below you will also find the user messages from the past. Always take that into account too.\n\n";

        $conversation = Conversations::query()
            ->where('api_id', '=', $conversation_id)
            ->first();

        foreach ($queryResponse->ids[0] as $id) {
            $file = Files::query()
                ->where('embedding_id', '=', $id)
                ->first();

            $count = ConversationHasDocument::query()
                ->where('conversation_id', '=', $conversation->id)
                ->where('file_id', '=', $file->id)
                ->count();

            // If document is already embedded in context
            if ($count > 0) {
                continue;
            }

            ConversationHasDocument::query()
                ->create([
                    'conversation_id' => $conversation->id,
                    'file_id' => $file->id
                ]);

            $enhancedMessage .= "###################\n";
            $enhancedMessage .= "Context Document:\n" . $file->content . "\n";
            $enhancedMessage .= "###################\n";
        }

        /*

        $index = 1;
        if ($pastMessages) {
            foreach ($pastMessages as $pastMessage) {
                $enhancedMessage .= "----------\n";
                $enhancedMessage .= "Recent User Message $index:\n" . $pastMessage->user_message . "\n";
                $enhancedMessage .= "----------\n";
                $index++;
            }
        }

        */

        $enhancedMessage .= "\nCurrent User Message:\n" . $message;

        return $enhancedMessage;
    }

    public static function createEmbedding($model)
    {
        $model->embedding_id = substr($model->path, strrpos($model->path, '/') + 1);

        $pathToFile = storage_path() . '/app/' . $model->path;

        $filename = $model->name;

        // We need to manually create the files here, because the API endpoint
        // returns small artifacts of the pptx file. We do not want to store
        // the whole pptx file, but rather these small artifacts. Each artifact
        // represents an embedding.
        if (str_ends_with($filename, 'pptx')) {
            $token = HomeController::getBearerToken();

            if (is_array($token)) {
                if (file_exists($pathToFile)) {
                    unlink($pathToFile);
                }

                return [
                    'status' => false,
                    'message' => $token['reason'],
                ];
            }

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->asMultipart()
                ->post(config('api.url') . '/data/pptx-to-md', [
                    [
                        'name' => 'pptxfile',
                        'contents' => fopen($pathToFile, 'r'),
                        'headers' => [
                            'Content-Type' => 'application/octet-stream',
                        ],
                    ],
                ]);

            if ($response->failed()) {
                if (file_exists($pathToFile)) {
                    unlink($pathToFile);
                }

                return [
                    'status' => false,
                    'message' => $response->reason(),
                ];
            }

            $ids = [];
            $documents = [];
            $metadata = [];
            $index = 1;
            foreach ($response->json(['content']) as $content) {
                $embedding_id = Str::random(40) . '.txt';
                $path = storage_path() . '/app/uploads/' . $embedding_id;

                $contentOnSlide = "";
                foreach ($content['content'] as $item) {
                    $contentOnSlide .= "$item\n";
                }

                $f = fopen($path, 'w');
                fwrite($f, $contentOnSlide);
                fclose($f);

                $ids[] = $embedding_id;
                $documents[] = $contentOnSlide;
                $metadata[] = [
                    'filename' => $model->name . "_Artifact_$index",
                    'size' => filesize($path)
                ];

                Files::query()->create([
                    'embedding_id' => $embedding_id,
                    'name' => $model->name . "_Artifact_$index",
                    'path' => "uploads/$embedding_id",
                    'content' => $contentOnSlide,
                    'size' => filesize($path),
                    'user_id' => Auth::id(),
                    'collection_id' => $model->collection_id,
                    'parent_id' => $model->id
                ]);

                $index++;
            }
        }
        else {
            $text = file_get_contents($pathToFile);

            $model->content = $text;

            $ids = [$model->embedding_id];
            $documents = [$text];
            $metadata = [
                [
                    'filename' => $model->name,
                    'size' => $model->size,
                ]
            ];
        }

        try {
            $collection = Collections::query()->find($model->collection_id)->name;

            $collection = self::getCollection($collection);

            $collection->add(
                ids: $ids,
                metadatas: $metadata,
                documents: $documents
            );

        } catch (\Exception $exception) {
            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        $model->save();

        return [
            'status' => true,
        ];
    }

    public static function deleteEmbedding($model)
    {
        $collection = Collections::query()->find($model->collection_id)->name;

        try {
            $collection = self::getCollection($collection);

            $artifacts = Files::query()
                ->where('parent_id', '=', $model->id)
                ->get();

            foreach ($artifacts as $artifact) {
                $collection->delete([$artifact->embedding_id]);

                $pathToFile = storage_path() . '/app/' . $artifact->path;

                if (file_exists($pathToFile)) {
                    unlink($pathToFile);
                }
            }

            $collection->delete([$model->embedding_id]);

            $pathToFile = storage_path() . '/app/' . $model->path;

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }
        } catch (\Exception $exception) {
            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => true,
        ];
    }

    public static function createCollection($name)
    {
        try {
            $chromaDB = self::getClient();

            $embeddingFunction = self::getEmbeddingFunction();

            $chromaDB->createCollection($name, embeddingFunction: $embeddingFunction);
        } catch (\Exception $exception) {
            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => true,
        ];
    }

    public static function deleteCollection($model)
    {
        $files = Files::query()->where('collection_id', '=', $model->id)->get();

        try {
            $chromaDB = self::getClient();

            $chromaDB->deleteCollection($model->name);
        } catch (\Exception $exception) {
            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        foreach ($files as $file) {
            $pathToFile = storage_path() . '/app/' . $file->path;

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }
        }

        return [
            'status' => true,
        ];
    }

    public static function getCollection($collection)
    {
        $chromaDB = self::getClient();

        $embeddingFunction = self::getEmbeddingFunction();

        return $chromaDB->getCollection($collection, embeddingFunction: $embeddingFunction);
    }

    public static function getEmbeddingFunction()
    {
        return new JinaEmbeddingFunction(config('chromadb.jina_api_key'), 'jina-embeddings-v2-base-de');
    }

    public static function getClient()
    {
        return ChromaDB::factory()
            ->withHost(config('chromadb.chroma_host'))
            ->withPort(config('chromadb.chroma_port'))
            ->withDatabase(config('chromadb.chroma_database'))
            ->withTenant(config('chromadb.chroma_tenant'))
            ->withHttpClient(new Client([
                'base_uri' => config('chromadb.chroma_host') . ':' . config('chromadb.chroma_port'),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . config('chromadb.chroma_server_auth_credentials')
                ],
            ]))
            ->connect();
    }
}
