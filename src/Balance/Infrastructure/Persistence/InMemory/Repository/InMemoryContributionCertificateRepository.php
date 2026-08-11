<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateFilter;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContributionCertificateRepository implements ContributionCertificateRepository
{
    /** @param array<string, ContributionCertificate> $contributionCertificates */
    public function __construct(
        private array $contributionCertificates = [],
    ) {}

    public function add(ContributionCertificate $contributionCertificate): void
    {
        $this->contributionCertificates[(string) $contributionCertificate->id()] = $contributionCertificate;
    }

    public function find(ContributionCertificateId $contributionCertificateId): ?ContributionCertificate
    {
        return $this->contributionCertificates[(string) $contributionCertificateId] ?? null;
    }

    public function findByCode(string $code): ?ContributionCertificate
    {
        foreach ($this->contributionCertificates as $contributionCertificate) {
            if ($contributionCertificate->code() === $code) {
                return $contributionCertificate;
            }
        }

        return null;
    }

    public function findByVerificationToken(string $verificationToken): ?ContributionCertificate
    {
        foreach ($this->contributionCertificates as $contributionCertificate) {
            if ($contributionCertificate->verificationToken() === $verificationToken) {
                return $contributionCertificate;
            }
        }

        return null;
    }

    public function findByProjectContributionId(ProjectContributionId $projectContributionId): ?ContributionCertificate
    {
        foreach ($this->contributionCertificates as $contributionCertificate) {
            $certificateContributionId = $contributionCertificate->projectContributionId();

            if ($certificateContributionId instanceof ProjectContributionId && $certificateContributionId->equals($projectContributionId)) {
                return $contributionCertificate;
            }
        }

        return null;
    }

    private function matches(ContributionCertificate $contributionCertificate, ContributionCertificateFilter $filter): bool
    {
        $accountId = $contributionCertificate->accountId();
        $projectId = $contributionCertificate->projectId();

        if (null !== $filter->accountId && (null === $accountId || !$filter->accountId->equals($accountId))) {
            return false;
        }

        if (null !== $filter->projectId && (!$projectId instanceof ProjectId || !$filter->projectId->equals($projectId))) {
            return false;
        }

        if (null !== $filter->type && $filter->type !== $contributionCertificate->type()) {
            return false;
        }

        if (null !== $filter->status && $filter->status !== $contributionCertificate->status()) {
            return false;
        }

        return null === $filter->code || $filter->code === $contributionCertificate->code();
    }

    /** @return array<int, ContributionCertificate> */
    public function findByFilter(ContributionCertificateFilter $filter): array
    {
        $results = [];
        $offset = $filter->page * $filter->limit;

        foreach ($this->contributionCertificates as $contributionCertificate) {
            if (!$this->matches($contributionCertificate, $filter)) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $contributionCertificate;
        }

        return $results;
    }

    public function totalResultsByFilter(ContributionCertificateFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
