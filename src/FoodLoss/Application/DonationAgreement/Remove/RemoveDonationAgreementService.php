<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\Remove;

use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveDonationAgreementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private DonationAgreementRepository $repository, private DonationAgreementAuthorizer $authorizer) {}

    public function __invoke(RemoveDonationAgreementRequest $request, User $user): RemoveDonationAgreementResponse
    {
        $donationAgreement = $this->repository->find($request->donationAgreementId);

        if (null == $donationAgreement) {
            throw new \InvalidArgumentException('DonationAgreement not found');
        }

        $this->authorizer->denyUnlessCanDoThat($donationAgreement, SecurityAction::Remove, $user);

        $donationAgreement->remove();

        $this->repository->add($donationAgreement);

        return new RemoveDonationAgreementResponse(
            donationAgreementId: $donationAgreement->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveDonationAgreementRequest;
    }
}
