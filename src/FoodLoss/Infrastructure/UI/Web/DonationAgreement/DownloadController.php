<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DonationAgreement;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/donation-agreements/download/{donationAgreementId}', name: 'donation_agreements_download')]
class DownloadController extends AbstractController
{
    use GetDonationAgreementFromIdTrait;

    public function __invoke(
        string $donationAgreementId,
        DonationAgreementRepository $repository,
        LoggedUser $loggedUser,
    ): Response {
        $donationAgreement = $this->getDonationAgreementFromId($donationAgreementId, $repository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $donationAgreement);

        $path = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $donationAgreement->path();

        if (!file_exists($path)) {
            throw $this->createNotFoundException('El fichero no existe en el servidor.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'convenio-' . $donationAgreement->recipientOrganizationId() . '.pdf',
        );

        return $response;
    }
}
