<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\Update;

use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Application\DonationAgreement\DonationAgreementUploader;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateDonationAgreementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DonationAgreementRepository $donationAgreementRepository,
        private DonationAgreementAuthorizer $authorizer,
        private DonationAgreementUploader $fileUploader,
    ) {}

    public function __invoke(UpdateDonationAgreementRequest $request, User $user): UpdateDonationAgreementResponse
    {
        $donationAgreement = $this->donationAgreementRepository->find($request->donationAgreementId);

        if (null == $donationAgreement) {
            throw new \InvalidArgumentException('DonationAgreement not found');
        }

        $this->authorizer->denyUnlessCanDoThat($donationAgreement, SecurityAction::Edit, $user);

        assert($request->recipientOrganizationId instanceof RecipientOrganizationId);
        assert($request->startDate instanceof \DateTimeImmutable);
        assert($request->expirationDate instanceof \DateTimeImmutable);
        assert($request->uploadedBy instanceof UserId);

        if (null !== $request->temporaryFilePath && null !== $request->fileExtension) {
            assert($donationAgreement->accountId() instanceof AccountId);
            $relativePath = $this->fileUploader->upload($request->temporaryFilePath, $request->fileExtension, $donationAgreement->accountId(), $request->recipientOrganizationId);
        } else {
            $relativePath = $donationAgreement->path();
        }

        $donationAgreement->update(
            recipientOrganizationId: $request->recipientOrganizationId,
            startDate: $request->startDate,
            expirationDate: $request->expirationDate,
            uploadedBy: $request->uploadedBy,
            path: $relativePath,
        );

        $this->donationAgreementRepository->add($donationAgreement);

        return new UpdateDonationAgreementResponse(
            donationAgreementId: $donationAgreement->id(),
            path: $donationAgreement->path(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateDonationAgreementRequest;
    }
}
