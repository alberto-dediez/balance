<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryTest;

class InMemoryBrandingRepositoryTest extends BrandingRepositoryTest
{
    public function createSUT(): BrandingRepository
    {
        return new InMemoryBrandingRepository();
    }
}
