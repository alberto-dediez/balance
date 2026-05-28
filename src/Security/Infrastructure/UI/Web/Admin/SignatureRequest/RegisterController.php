<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Infrastructure\Symfony\Form\RegisterSignatureRequestType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Storage\FileUploader;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/signature-requests/register', name: 'admin_signature_requests_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterSignatureRequestService $registerSignatureRequest,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
        FileUploader $fileUploader
    ): Response {
        $account = $loggedAccount();

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new SignatureRequest(id: SignatureRequestId::generate(), accountId: $account?->id()));

        $form = $this->createForm(RegisterSignatureRequestType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerSignatureRequest, $loggedUser, $account, $fileUploader) {
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $form->get('documentToSignFile')->getData();
                $registerRequest = $form->getData();

                $registerRequest->documentToSignPath = $fileUploader($uploadedFile->getPathname(), $uploadedFile->guessExtension() ?? 'bin');
                $registerRequest->accountId = $account?->id();

                $registerSignatureRequest($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Solicitud de firma creada correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_signature_requests');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/admin/signature-requests/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
