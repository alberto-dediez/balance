<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DonationAgreement;

interface DonationAgreementRepository
{
    public function add(DonationAgreement $donationAgreement): void;

    public function remove(DonationAgreement $donationAgreement): void;

    public function find(DonationAgreementId $donationAgreementId): ?DonationAgreement;

    /** @return array<DonationAgreement> */
    public function findByFilter(DonationAgreementFilter $filter): array;

    public function totalResultsByFilter(DonationAgreementFilter $filter): int;

    /** @return array<DonationAgreement> */
    public function findExpiringSoon(int $days): array;
}
