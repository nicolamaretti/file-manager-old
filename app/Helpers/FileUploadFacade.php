<?php

namespace App\Helpers;

use \Illuminate\Support\Facades\Facade;

class FileUploadFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'FileManagerHelper';
    }
}
