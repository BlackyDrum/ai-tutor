<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\ConversationHasDocument;
use App\Models\Document;
use App\Models\Embedding;
use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Embeddings\JinaEmbeddingFunction;
use Codewithkyrian\ChromaDB\Embeddings\OpenAIEmbeddingFunction;
use Illuminate\Support\Str;

class ChromaController extends Controller
{
    public static function createPromptWithContext(
        $collection,
        $message,
        $conversation
    ) {
        $chromaCollection = self::getCollection($collection->name);

        $queryResponse = $chromaCollection->query(
            queryTexts: [$message],
            nResults: $collection->max_results,
            include: [] // No need for includes, because we only need the ids
        );

        // TODO: The PHP Adapter we are currently using for ChromaDB (see https://github.com/CodeWithKyrian/chromadb-php)
        //       does not yet offer details on embedding query metrics like prompt- and total_tokens.
        //       Consider enhancing the Adapter to include this functionality.

        $enhancedMessage = "\nUser Message:\n" . $message . "\n\n";

        foreach ($queryResponse->ids[0] as $id) {
            $embedding = Embedding::query()
                ->where('embedding_id', '=', $id)
                ->first();

            $count = ConversationHasDocument::query()
                ->where('conversation_id', '=', $conversation->id)
                ->where('embedding_id', '=', $embedding->id)
                ->count();

            // If document is already embedded in context
            if ($count > 0) {
                continue;
            }

            ConversationHasDocument::query()->create([
                'conversation_id' => $conversation->id,
                'embedding_id' => $embedding->id,
            ]);

            $enhancedMessage .= "\n\"\"\"\n";
            $enhancedMessage .=
                "Context Document:\n" . $embedding->content . "\n";
            $enhancedMessage .= "\"\"\"\n";
        }

        return $enhancedMessage;
    }

    public static function createEmbedding($model, $document)
    {
        $pathToFile = storage_path() . '/app/' . $model->embedding_id;

        $filename = $model->name;
        $collectionId = $model->collection_id;

        if (str_ends_with($filename, 'pptx')) {
            $slides = self::parsePPTX($pathToFile);

            $result = self::createEmbeddingFromJson($slides, $model, $document);

            // After processing the file into separate slides/pages, we don't
            // need the uploaded file anymore. So, we delete the model to avoid
            // storing unnecessary data.
            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        } elseif (str_ends_with($filename, 'json')) {
            $json = json_decode(file_get_contents($pathToFile), true);

            $result = self::createEmbeddingFromJson($json, $model, $document);

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        } elseif (str_ends_with($filename, 'txt')) {
            $text = file_get_contents($pathToFile);

            $model->embedding_id = Str::orderedUuid()->toString();
            $model->content = $text ?? '';
            $model->document_id = $document->id;

            $ids = [$model->embedding_id];
            $documents = [$text];
            $metadata = [
                [
                    'name' => $model->name,
                    'size' => $model->size,
                    'document' => $document->name,
                    'document_md5' => $document->md5,
                ],
            ];

            $model->save();
        } elseif (str_ends_with($filename, 'md')) {
            $markdown = file_get_contents($pathToFile);

            $result = self::createEmbeddingFromMarkdown(
                $markdown,
                $model,
                $document
            );

            $model->forceDelete();

            $ids = $result['ids'];
            $documents = $result['documents'];
            $metadata = $result['metadata'];
        } else {
            throw new \Exception(
                'Attempted to process a file with the wrong format'
            );
        }

        if (!empty($ids)) {
            $collection = Collection::query()->find($collectionId)->name;

            $collection = self::getCollection($collection);

            $collection->add(
                ids: $ids,
                metadatas: $metadata,
                documents: $documents
            );
        }
    }

    private static function parsePPTX($pathToFile)
    {
        $zip = new \ZipArchive();
        $data = [];

        if ($zip->open($pathToFile)) {
            $slideNumber = 0;

            while (
                $xmlIndex = $zip->locateName(
                    'ppt/slides/slide' . ($slideNumber + 1) . '.xml'
                )
            ) {
                $xmlData = $zip->getFromIndex($xmlIndex);
                $xml = simplexml_load_string($xmlData);

                $namespaces = $xml->getNamespaces(true);
                $slideData = [
                    'content' => [],
                    'title' => '',
                ];

                $xml->registerXPathNamespace('a', $namespaces['a']);
                $xml->registerXPathNamespace('p', $namespaces['p']);

                $textBoxes = $xml->xpath('//p:sp');
                if (!empty($textBoxes)) {
                    $titleTexts = $textBoxes[0]->xpath('.//a:t');
                    if (!empty($titleTexts)) {
                        $slideData['title'] = join(
                            '',
                            array_map(function ($t) {
                                return (string) $t;
                            }, $titleTexts)
                        );
                    }

                    // Remove the first text box which is the title
                    array_shift($textBoxes);

                    foreach ($textBoxes as $index => $sp) {
                        $texts = $sp->xpath('.//a:t');
                        if (!empty($texts)) {
                            $textString = join(
                                '',
                                array_map(function ($t) {
                                    return (string) $t;
                                }, $texts)
                            );
                            $slideData['content'][] = $textString;
                            //$slideData[(string) $index] = $textString;
                        }
                    }
                }

                $data['content'][(string) $slideNumber] = $slideData;
                $slideNumber++;
            }

            $zip->close();

            return $data;
        } else {
            throw new \Exception('Cannot open pptx file');
        }
    }

    private static function createAndStoreSlide(
        $model,
        $title,
        $body,
        $index,
        $document
    ) {
        $embedding_id = Str::orderedUuid()->toString();
        $contentOnSlide = "Title: $title\n$body";

        Embedding::query()->create([
            'embedding_id' => $embedding_id,
            'name' => $model->name . " Slide $index",
            'content' => $contentOnSlide,
            'size' => strlen($contentOnSlide),
            'collection_id' => $model->collection_id,
            'document_id' => $document->id,
        ]);

        $metadata = [
            'name' => $model->name . " Slide $index",
            'size' => strlen($contentOnSlide),
            'document' => $document->name,
            'document_md5' => $document->md5,
        ];

        return [
            'id' => $embedding_id,
            'document' => $contentOnSlide,
            'metadata' => $metadata,
        ];
    }

    private static function createEmbeddingFromMarkdown(
        $markdown,
        $model,
        $document
    ) {
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
                model: $model,
                title: substr($slide[0], 2),
                body: implode("\n", array_slice($slide, 1)),
                index: $index,
                document: $document
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

    private static function createEmbeddingFromJson($json, $model, $document)
    {
        $ids = [];
        $documents = [];
        $metadata = [];
        $index = 1;

        foreach ($json['content'] as $content) {
            $title = $content['title'];
            $body = implode("\n", $content['content']);

            $result = self::createAndStoreSlide(
                $model,
                $title,
                $body,
                $index,
                $document
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

    public static function updateEmbedding($model)
    {
        $collection = Collection::query()->find($model->collection_id)->name;

        $collection = self::getCollection($collection);

        $document = Document::query()->find($model->document_id);

        $collection->update(
            ids: [$model->embedding_id],
            metadatas: [
                [
                    'name' => $model->name,
                    'size' => strlen($model->content),
                    'document' => $document->name,
                    'document_md5' => $document->md5,
                ],
            ],
            documents: [$model->content]
        );

        $model->size = strlen($model->content);

        $model->save();
    }

    public static function deleteEmbedding($model)
    {
        $collection = Collection::query()->find($model->collection_id)->name;

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
            $embeddingFunction
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
        $embeddings = Embedding::query()
            ->where('collection_id', '=', $original->id)
            ->get();

        self::createCollection($copy);

        $originalCollection = self::getCollection($original->name);

        $ids = [];
        $allEmbeddings = [];
        $allMetadata = [];
        $allDocuments = [];

        foreach ($embeddings as $embedding) {
            $originDocument = Document::query()->find($embedding->document_id);
            $document = Document::query()->firstOrCreate([
                'name' => $originDocument->name,
                'collection_id' => $copy->id,
                'md5' => $originDocument->md5,
            ]);

            $replicate = $embedding
                ->replicate(['created_at', 'updated_at'])
                ->fill([
                    'embedding_id' => Str::orderedUuid()->toString(),
                    'collection_id' => $copy->id,
                    'document_id' => $document->id,
                ]);

            $result = $originalCollection->get(
                ids: [$embedding->embedding_id],
                include: ['embeddings', 'metadatas', 'documents']
            );

            $ids[] = $replicate->embedding_id;
            $allEmbeddings[] = $result->embeddings[0];
            $allMetadata[] = $result->metadatas[0];
            $allDocuments[] = $result->documents[0];

            $replicate->save();
        }

        if (!empty($ids)) {
            $copiedCollection = self::getCollection($copy->name);

            $copiedCollection->add(
                ids: $ids,
                embeddings: $allEmbeddings,
                metadatas: $allMetadata,
                documents: $allDocuments
            );
        }
    }

    public static function deleteCollection($model)
    {
        $chromaDB = self::getClient();

        $chromaDB->deleteCollection($model->name);
    }

    public static function getCollection($name)
    {
        $chromaDB = self::getClient();

        $embeddingFunction = self::getEmbeddingFunction();

        return $chromaDB->getCollection($name, $embeddingFunction);
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
        } elseif ($embeddingFunction == 'jina') {
            return new JinaEmbeddingFunction(
                config('api.jina_api_key'),
                config('api.jina_embedding_model')
            );
        }

        throw new \Exception(
            "Invalid embedding provider. Please choose 'jina' or 'openai'"
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
