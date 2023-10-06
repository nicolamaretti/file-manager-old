<?php

namespace App\Interfaces;

interface Zipable
{
    /** Ritorna lo zip dell'oggetto corrente
     * @return string
     */
    public function getZip(): string;
}
