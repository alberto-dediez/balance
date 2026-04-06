<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DonationAgreement;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\FoodLoss\Application\DonationAgreement\Update\UpdateDonationAgreementRequest;
use App\FoodLoss\Application\DonationAgreement\Update\UpdateDonationAgreementService;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateDonationAgreementType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/donation-agreements/edit/{donationAgreementId}', name: 'donation_agreements_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetDonationAgreementFromIdTrait;

    public function __invoke(
        string $donationAgreementId,
        DonationAgreementRepository $donationAgreementRepository,
        UpdateDonationAgreementService $updateDonationAgreement,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $donationAgreement = $this->getDonationAgreementFromId($donationAgreementId, $donationAgreementRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $donationAgreement);

        $updateRequest = new UpdateDonationAgreementRequest(
            donationAgreementId: $donationAgreement->id(),
            recipientOrganizationId: $donationAgreement->recipientOrganizationId(),
            startDate: $donationAgreement->startDate(),
            expirationDate: $donationAgreement->expirationDate(),
            uploadedBy: $donationAgreement->uploadedBy(),
        );

        $form = $this->createForm(UpdateDonationAgreementType::class, $updateRequest);

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateDonationAgreement, $user): void {
                /** @var UpdateDonationAgreementRequest $updateRequest */
                $updateRequest = $form->getData();

                /** @var UploadedFile|null $file */
                $file = $form->get('pdfFile')->getData();

                if ($file) {
                    $updateRequest->temporaryFilePath = $file->getPathname();
                    $updateRequest->fileExtension = (string) $file->guessExtension();
                    $updateRequest->uploadedBy = $user->id();
                }

                $updateDonationAgreement($updateRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Convenio de donación actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('donation_agreements');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/donation_agreement/edit.html.twig', [
            'donationAgreement' => $donationAgreement,
            'form' => $form->createView(),
        ]);
    }
}
