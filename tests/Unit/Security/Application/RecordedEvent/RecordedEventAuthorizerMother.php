<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\RecordedEvent;

use App\Security\Application\RecordedEvent\RecordedEventAuthorizer;

class RecordedEventAuthorizerMother
{
    public static function create(): RecordedEventAuthorizer
    {
        return new RecordedEventAuthorizer();
    }
}
