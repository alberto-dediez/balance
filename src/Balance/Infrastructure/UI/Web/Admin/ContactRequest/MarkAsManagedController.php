<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ContactRequest;

use App\Balance\Application\ContactRequest\MarkAsManaged\MarkContactRequestAsManagedRequest;
use App\Balance\Application\ContactRequest\MarkAsManaged\MarkContactRequestAsManagedService;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyResolvedException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/contact-requests/{contactRequestId}/manage', name: 'admin_contact_requests_manage', methods: ['GET'])]
#[CsrfProtected('contact_request_manage')]
class MarkAsManagedController extends AbstractController
{
    use GetContactRequestFromIdTrait;

    public function __invoke(
        string $contactRequestId,
        ContactRequestRepository $contactRequestRepository,
        MarkContactRequestAsManagedService $service,
        LoggedUser $loggedUser,
    ): Response {
        $contactRequest = $this->getContactRequestFromId($contactRequestId, $contactRequestRepository);

        try {
            $service(new MarkContactRequestAsManagedRequest($contactRequest->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Solicitud marcada como gestionada'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (ContactRequestAlreadyResolvedException) {
            $this->addFlash('warning', new TranslatableMessage('La solicitud ya estaba resuelta'));
        }

        return $this->redirectToRoute('admin_contact_requests');
    }
}
