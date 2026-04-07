<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DonationAgreement;

use App\FoodLoss\Application\DonationAgreement\Remove\RemoveDonationAgreementRequest;
use App\FoodLoss\Application\DonationAgreement\Remove\RemoveDonationAgreementService;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/donation-agreements/remove/{donationAgreementId}', name: 'donation_agreements_remove')]
class RemoveController extends AbstractController
{
    use GetDonationAgreementFromIdTrait;

    public function __invoke(
        string $donationAgreementId,
        DonationAgreementRepository $donationAgreementRepository,
        RemoveDonationAgreementService $removeDonationAgreement,
        LoggedUser $loggedUser,
    ): Response {
        $donationAgreement = $this->getDonationAgreementFromId($donationAgreementId, $donationAgreementRepository);

        $this->denyAccessUnlessGranted(Action::Remove->value, $donationAgreement);

        try {
            $removeDonationAgreement(new RemoveDonationAgreementRequest($donationAgreement->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el convenio de donación'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el convenio de donación'));
        }

        return $this->redirectToRoute('donation_agreements');
    }
}
