<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Branding;

use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;

class BrandingRepositoryMother
{
    public static function inMemory(): InMemoryBrandingRepository
    {
        return new InMemoryBrandingRepository();
    }
}
