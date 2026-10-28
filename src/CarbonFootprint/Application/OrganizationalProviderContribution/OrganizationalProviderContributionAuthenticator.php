<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;

final class OrganizationalProviderContributionAuthenticator
{
    public function __construct(
        private OrganizationalProviderContributionRepository $contributionRepository,
        private ProviderRepository $providerRepository,
        private UserRepository $userRepository,
        private OrganizationalCarbonFootprintLocator $locator,
    ) {}

    public function authenticate(#[\SensitiveParameter] string $token): OrganizationalProviderContributionAuthentication
    {
        $contribution = $this->contributionRepository->findByToken($token);

        if (null === $contribution || !$contribution->isRequested()) {
            throw new OrganizationalProviderContributionTokenInvalidException();
        }

        if ($contribution->status()->isIgnored()) {
            throw new OrganizationalProviderContributionIgnoredException();
        }

        $providerId = $contribution->providerId();
        $provider = $providerId instanceof ProviderId ? $this->providerRepository->find($providerId) : null;
        $userId = $provider?->userId();
        $user = $userId instanceof UserId ? $this->userRepository->find($userId) : null;

        if (null === $user) {
            throw new OrganizationalProviderContributionTokenInvalidException();
        }

        return new OrganizationalProviderContributionAuthentication($contribution, $user);
    }

    public function locateCarbonFootprint(OrganizationalProviderContributionAuthentication $authentication): LocatedOrganizationalCarbonFootprint
    {
        $contribution = $authentication->contribution;

        try {
            return $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId());
        } catch (\InvalidArgumentException) {
            throw new OrganizationalProviderContributionTokenInvalidException();
        }
    }
}
