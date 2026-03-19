<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

class NotImportedException extends \Exception
{
    public function __construct($message = 'File not imported')
    {
        parent::__construct($message, 500);
    }
}
