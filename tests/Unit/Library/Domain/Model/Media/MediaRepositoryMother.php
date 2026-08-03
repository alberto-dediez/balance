<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Media;

use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;

class MediaRepositoryMother
{
    public static function inMemory(): InMemoryMediaRepository
    {
        return new InMemoryMediaRepository();
    }
}
