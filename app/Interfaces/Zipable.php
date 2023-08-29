<?php

namespace App\Interfaces;

use ZipArchive;

interface Zipable
{
    public function getZipFolder(): string;
}
