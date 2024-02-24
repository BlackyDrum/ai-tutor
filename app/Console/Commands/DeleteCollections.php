<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\ChromaController;
use App\Models\Collections;
use App\Models\Files;
use Illuminate\Console\Command;

class DeleteCollections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-collections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chromaDB = ChromaController::getClient();

        $chromaDB->deleteAllCollections();

        self::deleteDirectory(storage_path() . '/app/uploads');

        Collections::query()->delete();

        Files::query()->delete();
    }

    private function deleteDirectory($dirPath) {
        if (!is_dir($dirPath)) {
            return;
        }

        if (!str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dirPath);
    }
}
