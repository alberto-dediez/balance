<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution;

use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;

final class ProviderContributionAuthenticator
{
    public function __construct(
        private ProviderContributionRepository $providerContributionRepository,
        private ProviderRepository $providerRepository,
        private UserRepository $userRepository,
    ) {}

    public function authenticate(#[\SensitiveParameter] string $token): ProviderContributionAuthentication
    {
        $providerContribution = $this->providerContributionRepository->findByToken($token);

        if (null === $providerContribution) {
            throw new ProviderContributionTokenInvalidException();
        }

        if ($providerContribution->status()->isIgnored()) {
            throw new ProviderContributionIgnoredException();
        }

        $providerId = $providerContribution->providerId();
        $provider = $providerId instanceof ProviderId ? $this->providerRepository->find($providerId) : null;
        $userId = $provider?->userId();
        $user = $userId instanceof UserId ? $this->userRepository->find($userId) : null;

        if (null === $user) {
            throw new ProviderContributionTokenInvalidException();
        }

        return new ProviderContributionAuthentication($providerContribution, $user);
    }
}
