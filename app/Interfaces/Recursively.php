<?php

namespace App\Interfaces;

interface Recursively
{
    /** Get the current folder children ids (comprende anche l'id della folder corrente)
     * @return array
     */
    public function getChildrenIds(): array;

    /** Get the current folder full path
     * @return string
     */
    public function getFullPath(): string;

    /** Copy the current folder inside another folder
     *  - di default, la folder viene duplicata all'interno della stessa cartella
     * @return void
     */
    public function copyFolder(int $destinationFolderId = null): void;

    /** Get current folder ancestors
     * @return void
     */
    public function getAncestors(): array;
}
