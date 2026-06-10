<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest;

use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestRequest;
use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/signature-requests/remove/{signatureRequestId}', name: 'admin_signature_requests_remove')]
class RemoveController extends AbstractController
{
    use GetSignatureRequestFromIdTrait;

    public function __invoke(
        string $signatureRequestId,
        SignatureRequestRepository $signatureRequestRepository,
        RemoveSignatureRequestService $removeSignatureRequest,
        LoggedUser $loggedUser,
    ): Response {
        $signatureRequest = $this->getSignatureRequestFromId($signatureRequestId, $signatureRequestRepository);

        try {
            $removeSignatureRequest(new RemoveSignatureRequestRequest($signatureRequest->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la solicitud de firma {id}', ['{id}' => $signatureRequest->id()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la solicitud de firma {id}', ['{id}' => $signatureRequest->id()]));
        }

        return $this->redirectToRoute('admin_signature_requests');
    }
}
