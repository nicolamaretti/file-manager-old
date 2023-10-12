<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\App;
use App\Services\FileSharingService;
use App\Services\ImportFromFTPService;

class ImportFromFTP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fileimport:ftp {file} {--uuid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importazione file da FTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // dd($this->argument('file'), $this->option('uuid'));

        $uploadedFile = $this->argument('file');
        $folderUuid = $this->option('uuid');

        /* Istanziazione dei Service per fare l'upload */
        $fileSharingSrv = App::make(FileSharingService::class);
        $importFromFTPSrv = App::make(ImportFromFTPService::class);
        $uploadFileSrv = App::make(UploadFileService::class);

        $this->info('===> INIZIO IMPORTAZIONE FILES DA FTP');
        
        $fileSharingSrv->upload($importFromFTPSrv, $uploadFileSrv, $uploadedFile, $folderUuid);
    }
}
