<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

interface ContributionCertificateStorage
{
    public function store(ContributionCertificateId $id, ?AccountId $accountId, Locale $locale, string $localPath): string;

    /** @return resource */
    public function read(string $path): mixed;

    public function delete(?string $path): void;
}
