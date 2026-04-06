<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Type;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\AggregateRootIdTypeTrait;
use Doctrine\DBAL\Types\Type;

class DonationAgreementIdType extends Type
{
    use AggregateRootIdTypeTrait;

    public function getName(): string
    {
        return 'donation_agreement_id';
    }

    public function getClassName(): string
    {
        return DonationAgreementId::class;
    }
}
