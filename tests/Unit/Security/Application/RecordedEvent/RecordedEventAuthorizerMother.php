<?php

namespace App\Tests\Unit\Security\Application\RecordedEvent;

use App\Security\Application\RecordedEvent\RecordedEventAuthorizer;

class RecordedEventAuthorizerMother
{
    public static function create(): RecordedEventAuthorizer
    {
        return new RecordedEventAuthorizer();
    }
}