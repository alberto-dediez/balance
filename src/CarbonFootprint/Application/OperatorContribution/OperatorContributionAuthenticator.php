<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionTokenInvalidException;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;

final class OperatorContributionAuthenticator
{
    public function __construct(
        private OperatorContributionRepository $contributionRepository,
        private UserRepository $userRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
    ) {}

    public function authenticate(#[\SensitiveParameter] string $token): OperatorContributionAuthentication
    {
        $contribution = $this->contributionRepository->findByToken($token);

        if (null === $contribution || $contribution->isRemoved()) {
            throw new OperatorContributionTokenInvalidException();
        }

        if ($contribution->status()->isIgnored()) {
            throw new OperatorContributionIgnoredException();
        }

        $userId = $contribution->userId();
        $user = $userId instanceof UserId ? $this->userRepository->find($userId) : null;

        if (null === $user) {
            throw new OperatorContributionTokenInvalidException();
        }

        return new OperatorContributionAuthentication($contribution, $user);
    }

    public function locateCompanyCarbonFootprint(OperatorContributionAuthentication $authentication): CompanyCarbonFootprint
    {
        $companyCarbonFootprintId = $authentication->contribution->companyCarbonFootprintId();
        $companyCarbonFootprint = $companyCarbonFootprintId instanceof CompanyCarbonFootprintId
            ? $this->companyCarbonFootprintRepository->find($companyCarbonFootprintId)
            : null;

        if (null === $companyCarbonFootprint || $companyCarbonFootprint->isRemoved()) {
            throw new OperatorContributionTokenInvalidException();
        }

        return $companyCarbonFootprint;
    }

    public function ensureEditable(CompanyCarbonFootprint $companyCarbonFootprint): void
    {
        if ($companyCarbonFootprint->isCompleted()) {
            throw new OperatorContributionClosedException();
        }
    }
}
