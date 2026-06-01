<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\SignatureRequest;

use App\Security\Application\Action;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Security\Domain\Model\SignatureRequest\SignatureDocumentViewed;
use App\Security\Domain\Model\SignatureRequest\SignatureOtpRequested;
use App\Security\Domain\Model\SignatureRequest\SignatureOtpVerified;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestExpired;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRegistered;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRemoved;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\AggregateHistoryTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/signature-requests/{signatureRequestId}/history', name: 'admin_signature_requests_history')]
class HistoryController extends AbstractController
{
    use AggregateHistoryTrait;
    use GetSignatureRequestFromIdTrait;

    protected function allowedEventNames(): array
    {
        return [
            DocumentSigned::eventName(),
            SignatureDocumentViewed::eventName(),
            SignatureOtpRequested::eventName(),
            SignatureOtpVerified::eventName(),
            SignatureRequestExpired::eventName(),
            SignatureRequestRegistered::eventName(),
            SignatureRequestRemoved::eventName(),
        ];
    }

    protected function allowedContext(): string
    {
        return 'security';
    }

    public function __invoke(
        string $signatureRequestId,
        ListRecordedEventsService $listEvents,
        SignatureRequestRepository $signatureRepository,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $signature = $this->getSignatureRequestFromId($signatureRequestId, $signatureRepository);

        $this->denyAccessUnlessGranted(Action::History->value, $signature);

        $pageTitle = $translator->trans('Solicitudes de firma')
            . ' > '
            . (string) $signature
            . ' > '
            . $translator->trans('Histórico')
        ;

        return $this->renderAggregateHistory(
            aggregateRootId: $signatureRequestId,
            pageTitle: $pageTitle,
            backToList: $this->generateUrl('admin_signature_requests'),
            listEvents: $listEvents,
            performBy: $loggedUser(),
            request: $request,
        );
    }
}
