<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string foldername
     * @var string uuid
     */
    protected $signature = 'test:create-folder {foldername} {--uuid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test creazione folder (se non si specifica l\'UUID della parent folder, sarà creata una root folder)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $folderName = $this->argument('foldername');
        $folderUuid = $this->option('uuid');

        $parentFolder = File::query()
            ->where('is_folder', true)
            ->where('uuid', $folderUuid)
            ->first();
        // dd($parentFolder->id);

        $created = File::create([
            'name' => $folderName,
            'path' => strtolower($folderName),
            'is_folder' => true,
            'uuid' => Str::uuid(),
            'created_by' => $parentFolder->created_by,
        ]);

        if ($parentFolder) {
            $created->file_id = $parentFolder->id;
            $created->save();
        }

        info("La cartella $folderName è stata creata con successo.");
    }
}
