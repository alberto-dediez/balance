<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Resend;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ResendProviderContributionNotificationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProviderContributionRepository $providerContributionRepository,
        private ProviderContributionAuthorizer $authorizer,
    ) {}

    public function __invoke(ResendProviderContributionNotificationRequest $request, User $user): ResendProviderContributionNotificationResponse
    {
        $providerContribution = $this->providerContributionRepository->find($request->providerContributionId);

        if (null == $providerContribution) {
            throw new \InvalidArgumentException('ProviderContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($providerContribution, SecurityAction::Edit, $user);

        if ($providerContribution->status()->isIgnored()) {
            throw new \InvalidArgumentException('Cannot resend notification for an ignored ProviderContribution');
        }

        $providerContribution->resendNotification();

        $this->providerContributionRepository->add($providerContribution);

        return new ResendProviderContributionNotificationResponse();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ResendProviderContributionNotificationRequest;
    }
}
