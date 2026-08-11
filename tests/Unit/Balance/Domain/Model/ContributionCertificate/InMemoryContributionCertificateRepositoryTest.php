<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;

class InMemoryContributionCertificateRepositoryTest extends ContributionCertificateRepositoryTest
{
    protected function createSUT(): ContributionCertificateRepository
    {
        return ContributionCertificateRepositoryMother::inMemory();
    }
}
