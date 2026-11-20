<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;

interface ContactRequestRepository
{
    public function add(ContactRequest $contactRequest): void;

    public function find(ContactRequestId $contactRequestId): ?ContactRequest;

    public function findOneByOrigin(ProjectContributionOrigin $origin, string $originId): ?ContactRequest;

    /** @return array<ContactRequest> */
    public function findByFilter(ContactRequestFilter $filter): array;

    public function totalResultsByFilter(ContactRequestFilter $filter): int;
}
