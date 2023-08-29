<?php

namespace App\Interfaces;

interface Recursively
{
    public function getChildrenIds(): array;
    public function getFullPath(): string;
    public function copyFolder(int $destinationFolderId): void;
}
