<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ContactRequest;

use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contact-requests/{contactRequestId}', name: 'admin_contact_requests_view', methods: ['GET'])]
class ViewController extends AbstractController
{
    use GetContactRequestFromIdTrait;

    public function __invoke(
        string $contactRequestId,
        ContactRequestRepository $contactRequestRepository,
        CountryRepository $countryRepository,
    ): Response {
        $contactRequest = $this->getContactRequestFromId($contactRequestId, $contactRequestRepository);

        $this->denyAccessUnlessGranted(Action::View->value, $contactRequest);

        return $this->render('@balance/admin/contact-requests/view.html.twig', [
            'contactRequest' => $contactRequest,
            'countryName' => $countryRepository->findByIso($contactRequest->contactCountry())?->name() ?? $contactRequest->contactCountry(),
        ]);
    }
}
