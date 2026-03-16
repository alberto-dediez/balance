<?php

namespace App\Shared\Domain\Uuid;

use Symfony\Component\Uid\Uuid;

class UuidGenerator
{
    public static function v4(): string
    {
        return Uuid::v4()->toString();
    }

    public static function v7(): string
    {
        return Uuid::v7()->toString();
    }
}
