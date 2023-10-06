<?php

namespace App\Interfaces;

use App\Exceptions\FileAlreadyExistsException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\UnauthorizedException;

interface Recursively
{
    /**
     * Ritorna un array contenente gli id delle sottocartelle
     *
     * @return array $childrenIds
     */
    public function getChildrenIds(): array;

    /**
     * Ritorna un array contenente i nomi delle cartelle dalla root alla cartella corrente
     *
     * @return array $ancestors
     */
    public function getAncestors(): array;

    /**
     * @throws AuthenticationException
     * @throws FileAlreadyExistsException
     */
    public function rename(string $newName): void;

    /**
     * @throws UnauthorizedException
     * @throws AuthenticationException
     */
    public function copy(): void;

    /**
     * @throws AuthenticationException
     */
    public function move(int $moveIntoFolderId): void;
}
