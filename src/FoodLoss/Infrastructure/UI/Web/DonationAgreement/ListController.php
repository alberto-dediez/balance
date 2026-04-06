<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DonationAgreement;

use App\Security\Application\Action;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\Security\Domain\Model\Account\Account;
use App\FoodLoss\Application\DonationAgreement\List\ListDonationAgreementsRequest;
use App\FoodLoss\Application\DonationAgreement\List\ListDonationAgreementsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListDonationAgreementsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/donation-agreements', name: 'donation_agreements')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListDonationAgreementsService $listDonationAgreements,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $this->denyAccessUnlessGranted(
            Action::List->value,
            new DonationAgreement(
                id: DonationAgreementId::generate(),
                accountId: $account?->id(),
            ),
        );

        assert($account instanceof Account);

        try {
            [$filter, $form] = $this->filter($request, function () use ($account): ListDonationAgreementsRequest {
                $request = new ListDonationAgreementsRequest();
                $request->accountId = $account->id();

                return $request;
            }, ListDonationAgreementsType::class);

            $response = $listDonationAgreements($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/donation_agreement/list.html.twig', [
            'donationAgreements' => $response->donationAgreements,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
