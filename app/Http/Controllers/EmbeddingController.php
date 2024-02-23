<?php

namespace App\Http\Controllers;

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
        $files = Files::all();
        return Inertia::render('Admin/Embeddings', [
            'files' => $files
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|string|exists:files,id'
        ]);

        self::deleteEmbedding($request->input('id'));

        return response()->json(['id' => $request->input('id')]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,txt'
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
            'user_id' => Auth::id()
        ]);

        $pathToFile = storage_path() . '/app/' . $createdFile->path;

        if ($file->getClientMimeType() == 'application/pdf') {
            $parser = new Parser();

            try {
                $pdf = $parser->parseFile($pathToFile);
            } catch (\Exception $exception) {
                DB::rollBack();

                return response()->json(['message' => $exception->getMessage()], 422);
            }

            $text = $pdf->getText();
        }
        else {
            $text = file_get_contents($pathToFile);
        }

        self::createEmbedding($createdFile, $text);

        DB::commit();
    }

    private function deleteEmbedding($id)
    {
        $collection = self::getCollection();

        $collection->delete([$id]);

        Files::query()->find($id)->delete();
    }

    private function createEmbedding($file, $text)
    {
        $collection = self::getCollection();

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

    private function getCollection()
    {
        $chromaDB = ChromaDB::client();

        $embeddingFunction = new JinaEmbeddingFunction(config('api.jina_api_key'));

        return $chromaDB->getCollection(config('api.collection_name'), embeddingFunction: $embeddingFunction);
    }
}
