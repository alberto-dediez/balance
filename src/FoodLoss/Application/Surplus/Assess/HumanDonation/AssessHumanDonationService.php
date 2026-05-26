<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Assess\HumanDonation;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AssessHumanDonationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private SurplusRepository $surplusRepository,
        private SurplusAuthorizer $authorizer,
    ) {}

    public function __invoke(AssessHumanDonationRequest $request, User $user): AssessHumanDonationResponse
    {
        $surplus = $this->surplusRepository->find($request->surplusId);

        if (null === $surplus) {
            throw new \InvalidArgumentException('Surplus not found');
        }

        $this->authorizer->denyUnlessCanDoThat($surplus, SecurityAction::Edit, $user);

        $surplus->assessHumanDonation(
            canBeHumanDonated: $request->canBeHumanDonated,
            reasonId: !$request->canBeHumanDonated ? $request->reasonId : null,
            donationDate: $request->donationDate,
            batch: $request->batch,
            recipientOrganizationId: $request->recipientOrganizationId,
            createDeliveryLog: $request->createDeliveryLog,
        );

        $this->surplusRepository->add($surplus);

        return new AssessHumanDonationResponse(surplusId: $surplus->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AssessHumanDonationRequest;
    }
}
