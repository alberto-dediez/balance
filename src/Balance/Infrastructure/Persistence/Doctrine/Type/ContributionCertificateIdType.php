<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Type;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class ContributionCertificateIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'contribution_certificate_id';
    }

    public function getClassName(): string
    {
        return ContributionCertificateId::class;
    }
}
