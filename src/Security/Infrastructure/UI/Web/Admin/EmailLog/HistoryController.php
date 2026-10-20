<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\EmailLog;

use App\Security\Application\Action;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogBounced;
use App\Security\Domain\Model\EmailLog\EmailLogClicked;
use App\Security\Domain\Model\EmailLog\EmailLogDeferred;
use App\Security\Domain\Model\EmailLog\EmailLogDelivered;
use App\Security\Domain\Model\EmailLog\EmailLogDropped;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogOpened;
use App\Security\Domain\Model\EmailLog\EmailLogRegistered;
use App\Security\Domain\Model\EmailLog\EmailLogRemoved;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use App\Security\Domain\Model\EmailLog\EmailLogSpamReported;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\AggregateHistoryTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/emails/{emailLogId}/history', name: 'admin_emails_history', methods: ['GET'])]
#[IsGranted('ROLE_SUPER_ADMIN')]
class HistoryController extends AbstractController
{
    use AggregateHistoryTrait;

    protected function allowedEventNames(): array
    {
        return [
            EmailLogRegistered::eventName(),
            EmailLogDelivered::eventName(),
            EmailLogDeferred::eventName(),
            EmailLogBounced::eventName(),
            EmailLogDropped::eventName(),
            EmailLogOpened::eventName(),
            EmailLogClicked::eventName(),
            EmailLogSpamReported::eventName(),
            EmailLogRemoved::eventName(),
        ];
    }

    protected function allowedContext(): string
    {
        return 'security';
    }

    public function __invoke(
        string $emailLogId,
        ListRecordedEventsService $listEvents,
        EmailLogRepository $repository,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $emailLog = $repository->find(new EmailLogId($emailLogId));

        if (!$emailLog instanceof EmailLog) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(Action::History->value, $emailLog);

        $pageTitle = $translator->trans('Emails enviados')
            . ' > '
            . $emailLog->subject()
            . ' > '
            . $translator->trans('Histórico')
        ;

        return $this->renderAggregateHistory(
            aggregateRootId: $emailLogId,
            pageTitle: $pageTitle,
            backToList: $this->generateUrl('admin_emails'),
            listEvents: $listEvents,
            performBy: $loggedUser(),
            request: $request,
        );
    }
}
