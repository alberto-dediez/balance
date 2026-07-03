<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\ImportationProfile;

use App\DataExchange\Application\ImportationProfile\ImportationProfileAuthorizer;

class ImportationProfileAuthorizerMother
{
    public static function create(): ImportationProfileAuthorizer
    {
        return new ImportationProfileAuthorizer();
    }
}
