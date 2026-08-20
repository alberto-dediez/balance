<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation;

use App\DataExchange\Application\Exportation\ExportationAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;

class ExportationAuthorizerMother
{
    public static function create(): ExportationAuthorizer
    {
        return new ExportationAuthorizer();
    }

    public static function withCurrentAccount(?AccountId $accountId): ExportationAuthorizer
    {
        return new ExportationAuthorizer(new StubCurrentAccountIdProvider($accountId));
    }
}
