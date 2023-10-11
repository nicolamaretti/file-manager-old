<?php

namespace App\Helpers\Facade;

use Illuminate\Support\Facades\Facade;

class FileUploader extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'FileUploaderHelper';
    }
}
