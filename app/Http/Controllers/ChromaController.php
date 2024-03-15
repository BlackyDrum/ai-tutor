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
    public static function createPromptWithContext($collectionName, $message, $conversation_id)
    {
        $collection = self::getCollection($collectionName);

        $maxResults = Collections::query()
            ->where('name', '=', $collectionName)
            ->first()
            ->max_results;

        $queryResponse = $collection->query(
            queryTexts: [
                $message
            ],
            nResults: $maxResults
        );

        $enhancedMessage = "";

        $conversation = Conversations::query()
            ->where('url_id', '=', $conversation_id)
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

            $enhancedMessage .= "\n\"\"\"\n";
            $enhancedMessage .= "Context Document:\n" . $file->content . "\n";
            $enhancedMessage .= "\"\"\"\n";
        }

        $enhancedMessage .= "\n\nUser Message:\n" . $message . "\n";

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

            try {
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
            } catch (\Exception $exception) {
                Log::error('App/ConversAItion: Failed to convert pptx to json. Reason: {message}', [
                    'message' => $exception->getMessage(),
                ]);

                if (file_exists($pathToFile)) {
                    unlink($pathToFile);
                }

                return [
                    'status' => false,
                    'message' => $exception->getMessage(),
                ];
            }

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            if ($response->failed()) {
                Log::error('ConversAItion: Failed to convert pptx to json. Reason: {reason}. Status: {status}', [
                    'reason' => $response->reason(),
                    'status' => $response->status(),
                ]);

                return [
                    'status' => false,
                    'message' => $response->reason(),
                ];
            }

            $result = self::createEmbeddingFromJson($response->json(), $model);

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];

        }
        elseif (str_ends_with($filename, 'json')) {
            $json = json_decode(file_get_contents($pathToFile), true);

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            $result = self::createEmbeddingFromJson($json, $model);

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        }
        elseif (str_ends_with($filename, 'txt')) {
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
                ]
            ];

            $model->user_id = Auth::id();

            $model->save();
        }
        elseif (str_ends_with($filename, 'md')) {
            $markdown = file_get_contents($pathToFile);

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            $result = self::createEmbeddingFromMarkdown($markdown, $model);

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        }
        else {
            Log::warning('App: Attempted to process a file with the wrong format', [
                'name' => $model->name
            ]);

            if (file_exists($pathToFile)) {
                unlink($pathToFile);
            }

            return [
                'status' => false,
                'message' => 'Wrong file format',
            ];
        }

        try {
            $collection = Collections::query()->find($collectionId)->name;

            $collection = self::getCollection($collection);

            $collection->add(
                ids: $ids,
                metadatas: $metadata,
                documents: $documents
            );

        } catch (\Exception $exception) {
            Log::error('ChromaDB: Failed to add items to collection with ID {collection}. Reason: {reason}', [
                'collection' => $collectionId,
                'reason' => $exception->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => true,
        ];
    }

    private static function createAndStoreSlide($model, $title, $body, $index)
    {
        $embedding_id = Str::random(40) . '.slide';
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
                $slide = "# " . $slide;
            }

            $slide = array_values(array_filter(explode("\n", $slide), fn($element) => !empty(trim($element))));

            $result = self::createAndStoreSlide($model, substr($slide[0], 1), $slide[1], $index);

            $ids[] = $result['id'];
            $documents[] = $result['document'];
            $metadata[] = $result['metadata'];

            $index++;
        }

        return [
            'ids' => $ids,
            'documents' => $documents,
            'metadata' => $metadata
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
            'metadata' => $metadata
        ];
    }

    public static function updateEmbedding($model)
    {
        $collection = Collections::query()->find($model->collection_id)->name;

        try {
            $collection = self::getCollection($collection);

            $collection->update(
                ids: [$model->embedding_id],
                metadatas: [
                    [
                        'filename' => $model->name,
                        'size' => strlen($model->content)
                    ]
                ],
                documents: [$model->content]
            );

            $model->size = strlen($model->content);
        } catch (\Exception $exception) {
            Log::error('ChromaDB: Failed to update collection with ID {collection-id}. Reason: {reason}', [
                'collection-id' => $model->collection_id,
                'reason' => $exception->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => true,
        ];
    }

    public static function deleteEmbedding($model)
    {
        $collection = Collections::query()->find($model->collection_id)->name;

        try {
            $collection = self::getCollection($collection);

            $collection->delete([$model->embedding_id]);
        } catch (\Exception $exception) {
            Log::error('ChromaDB: Failed to delete items from collection with ID {collection-id}. Reason: {reason}', [
                'collection-id' => $model->collection_id,
                'reason' => $exception->getMessage(),
            ]);

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
            Log::error('ChromaDB: Failed to create new collection with name {collection}. Reason: {reason}', [
                'collection' => $name,
                'reason' => $exception->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => true,
        ];
    }

    public static function updateCollection($oldName, $newName) {
        try {
            $collection = self::getCollection($oldName);

            $collection->modify($newName, []);
        } catch (\Exception $exception) {
            Log::error('ChromaDB: Failed to update collection with name {name}. Reason: {reason}', [
                'name' => $oldName,
                'reason' => $exception->getMessage(),
            ]);

            // this is handled by nova
            throw $exception;
        }
    }

    public static function deleteCollection($model)
    {
        try {
            $chromaDB = self::getClient();

            $chromaDB->deleteCollection($model->name);
        } catch (\Exception $exception) {
            Log::error('ChromaDB: Failed to delete collection with ID {collection-id}. Reason: {reason}', [
                'collection-id' => $model->id,
                'reason' => $exception->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
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
        $embeddingFunction = config('chromadb.embedding_function');

        if ($embeddingFunction == 'openai') {
            return new OpenAIEmbeddingFunction(config('api.openai_api_key'));
        }

        return new JinaEmbeddingFunction(config('api.jina_api_key'), 'jina-embeddings-v2-base-de');
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
