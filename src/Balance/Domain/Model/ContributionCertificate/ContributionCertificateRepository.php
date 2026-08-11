<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

interface ContributionCertificateRepository
{
    public function add(ContributionCertificate $contributionCertificate): void;

    public function find(ContributionCertificateId $contributionCertificateId): ?ContributionCertificate;

    public function findByCode(string $code): ?ContributionCertificate;

    public function findByVerificationToken(string $verificationToken): ?ContributionCertificate;

    public function findByProjectContributionId(ProjectContributionId $projectContributionId): ?ContributionCertificate;

    /** @return array<ContributionCertificate> */
    public function findByFilter(ContributionCertificateFilter $filter): array;

    public function totalResultsByFilter(ContributionCertificateFilter $filter): int;
}
