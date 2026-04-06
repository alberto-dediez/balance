<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\List;

use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListDonationAgreementsService
{
    public function __construct(private DonationAgreementRepository $repository, private DonationAgreementAuthorizer $authorizer) {}

    public function __invoke(ListDonationAgreementsRequest $request, User $user): ListDonationAgreementsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new DonationAgreement(id: DonationAgreementId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $donationAgreements = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListDonationAgreementsResponse($donationAgreements, $totalResults);
    }
}
