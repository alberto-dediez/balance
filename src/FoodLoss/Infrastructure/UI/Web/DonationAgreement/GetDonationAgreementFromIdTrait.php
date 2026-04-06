<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DonationAgreement;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;

trait GetDonationAgreementFromIdTrait
{
    private function getDonationAgreementFromId(string $donationAgreementId, DonationAgreementRepository $repository): DonationAgreement
    {
        $donationAgreement = $repository->find(new DonationAgreementId($donationAgreementId));

        if (null == $donationAgreement) {
            throw $this->createNotFoundException('Convenio de donación no encontrado');
        }

        return $donationAgreement;
    }
}
