<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContributionCertificateRepository;

class ContributionCertificateRepositoryMother
{
    public static function inMemory(): InMemoryContributionCertificateRepository
    {
        return new InMemoryContributionCertificateRepository();
    }
}
