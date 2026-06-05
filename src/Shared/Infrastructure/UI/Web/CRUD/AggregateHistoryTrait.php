<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait AggregateHistoryTrait
{
    /**
     * @return list<string>
     */
    abstract protected function allowedEventNames(): array;

    protected function renderAggregateHistory(
        string $aggregateRootId,
        string $pageTitle,
        string $backToList,
        ListRecordedEventsService $listEvents,
        User $performBy,
        Request $request,
    ): Response {
        $page = max(0, (int) $request->query->get('page', 0));
        $limit = max(1, (int) $request->query->get('limit', 25));

        $filter = new ListRecordedEventsRequest(
            context: $this->allowedContext(),
            aggregateRootId: $aggregateRootId,
            allowedEventNames: $this->allowedEventNames(),
            limit: $limit,
        );
        $filter->page = $page;

        try {
            $response = $listEvents($filter, $performBy);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@shared/history/show.html.twig', [
            'page_title' => $pageTitle,
            'events' => $response->events,
            'totalResults' => $response->totalResults,
            'page' => $page + 1,
            'limit' => $limit,
            'backToList' => $backToList,
        ]);
    }
}
