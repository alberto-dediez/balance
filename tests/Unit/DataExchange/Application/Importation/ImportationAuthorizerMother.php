<?php

namespace App\Tests\Unit\DataExchange\Application\Importation;

use App\DataExchange\Application\Importation\ImportationAuthorizer;

class ImportationAuthorizerMother
{
    public static function create(): ImportationAuthorizer
    {
        return new ImportationAuthorizer();
    }
}
