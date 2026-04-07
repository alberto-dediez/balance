<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DonationAgreement;

use App\FoodLoss\Application\DonationAgreement\Register\RegisterDonationAgreementRequest;
use App\FoodLoss\Application\DonationAgreement\Register\RegisterDonationAgreementService;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterDonationAgreementType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/donation-agreements/register', name: 'donation_agreements_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterDonationAgreementService $registerDonationAgreement,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new DonationAgreement(
            id: DonationAgreementId::generate(),
            accountId: $account?->id(),
        ));

        assert($account instanceof Account);

        $form = $this->createForm(RegisterDonationAgreementType::class, new RegisterDonationAgreementRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerDonationAgreement, $user, $account): true {
                /** @var RegisterDonationAgreementRequest $registerRequest */
                $registerRequest = $form->getData();

                $registerRequest->accountId = $account->id();
                $registerRequest->uploadedBy = $user->id();

                /** @var UploadedFile $file */
                $file = $form->get('pdfFile')->getData();

                $registerRequest->temporaryFilePath = $file->getPathname();
                $registerRequest->fileExtension = (string) $file->guessExtension();

                $registerDonationAgreement($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Convenio de donación creado correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('donation_agreements');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/donation_agreement/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
