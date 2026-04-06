<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\Register;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Application\DonationAgreement\DonationAgreementUploader;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterDonationAgreementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DonationAgreementRepository $donationAgreementRepository,
        private DonationAgreementAuthorizer $authorizer,
        private DonationAgreementUploader $fileUploader,
    ) {}

    public function __invoke(RegisterDonationAgreementRequest $request, User $user): RegisterDonationAgreementResponse
    {
        $id = DonationAgreementId::generate();

        $this->authorizer->denyUnlessCanDoThat(new DonationAgreement(id: $id, accountId: $request->accountId), SecurityAction::Register, $user);

        assert($request->recipientOrganizationId instanceof RecipientOrganizationId);
        assert($request->accountId instanceof AccountId);
        assert($request->startDate instanceof \DateTimeImmutable);
        assert($request->expirationDate instanceof \DateTimeImmutable);
        assert($request->uploadedBy instanceof UserId);

        $path = $this->fileUploader->upload($request->temporaryFilePath, $request->fileExtension, $request->accountId, $request->recipientOrganizationId);

        $donationAgreement = new DonationAgreement($id);
        $donationAgreement->register(
            recipientOrganizationId: $request->recipientOrganizationId,
            accountId: $request->accountId,
            startDate: $request->startDate,
            expirationDate: $request->expirationDate,
            uploadedBy: $request->uploadedBy,
            path: $path,
        );

        $this->donationAgreementRepository->add($donationAgreement);

        return new RegisterDonationAgreementResponse(
            donationAgreementId: $donationAgreement->id(),
            path: $donationAgreement->path(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterDonationAgreementRequest;
    }
}
