<?php

namespace App\DataExchange\Domain\Model\Importation;

class NotImportedException extends \Exception
{
    public function __construct()
    {
        parent::__construct('File not imported');
    }
}
