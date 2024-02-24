<?php

namespace App\Http\Controllers;

use App\Models\Collections;
use App\Models\Files;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Smalot\PdfParser\Parser;
use Codewithkyrian\ChromaDB\ChromaDB;
use Codewithkyrian\ChromaDB\Embeddings\JinaEmbeddingFunction;

class EmbeddingController extends Controller
{
    public function show()
    {
        $files = Files::query()
            ->join('collections', 'collections.id', '=', 'files.collection_id')
            ->select([
                'files.*',
                'collections.name AS collection'
            ])
            ->get();

        return Inertia::render('Admin/Embeddings', [
            'files' => $files
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|string|exists:files,id'
        ]);

        $collection = Collections::query()->find($request->input('id'));

        self::deleteEmbedding($request->input('id'), $collection->name);

        return response()->json(['id' => $request->input('id')]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,txt',
            'collection' => 'required|integer|exists:collections,id'
        ]);

        $file = $request->file('file');

        $path = $file->store('uploads');

        DB::beginTransaction();

        $createdFile = Files::query()->create([
            'id' => substr($path, strpos($path, '/') + 1),
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'user_id' => Auth::id(),
            'collection_id' => $request->input('collection')
        ]);

        $pathToFile = storage_path() . '/app/' . $createdFile->path;

        if ($file->getClientMimeType() == 'application/pdf') {
            $parser = new Parser();

            try {
                $pdf = $parser->parseFile($pathToFile);
            } catch (\Exception $exception) {
                DB::rollBack();

                unlink($pathToFile);

                return response()->json(['message' => 'Error parsing PDF'], 422);
            }

            $text = $pdf->getText();
        }
        else {
            $text = file_get_contents($pathToFile);
        }

        try {
            $collection = Collections::query()->find($request->input('collection'));

            self::createEmbedding($createdFile, $text, $collection->name);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            unlink($pathToFile);

            return response()->json(['message' => 'Error creating embedding'], 422);
        }

    }

    public function createCollection(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:collections,name'
        ]);

        $chromaDB = ChromaDB::client();

        $embeddingFunction = new JinaEmbeddingFunction(config('api.jina_api_key'));

        $chromaDB->createCollection($request->input('name'), embeddingFunction: $embeddingFunction);

        Collections::query()->create([
            'name' => $request->input('name')
        ]);
    }

    private function deleteEmbedding($id, $collection)
    {
        $collection = self::getCollection($collection);

        $collection->delete([$id]);

        $file = Files::query()->find($id);

        unlink(storage_path() . '/app/' . $file->path);

        $file->delete();
    }

    private function createEmbedding($file, $text, $collection)
    {
        $collection = self::getCollection($collection);

        $id = [$file->id];
        $document = [$text];
        $metadata = [
            [
                'filename' => $file->name,
                'size' => $file->size,
                'mime' => $file->mime
            ]
        ];

        $collection->add(
            ids: $id,
            metadatas: $metadata,
            documents: $document
        );
    }

    private function getCollection($collection)
    {
        $chromaDB = ChromaDB::client();

        $embeddingFunction = new JinaEmbeddingFunction(config('api.jina_api_key'));

        return $chromaDB->getCollection($collection, embeddingFunction: $embeddingFunction);
    }
}
