<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Persistence\InMemory\Repository;

use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Tests\Unit\Library\Domain\Model\Media\MediaRepositoryTest;

class InMemoryMediaRepositoryTest extends MediaRepositoryTest
{
    public function createSUT(): MediaRepository
    {
        return new InMemoryMediaRepository();
    }
}
