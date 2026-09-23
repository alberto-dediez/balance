<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GetCompensationSummary;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\EventCompensationCollector;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class GetCompensationSummaryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private GetResultsService $getResultsService,
        private EventCompensationCollector $compensationCollector,
    ) {}

    public function __invoke(GetCompensationSummaryRequest $request, User $user): GetCompensationSummaryResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $eventId = $eventCarbonFootprint->eventId();

        if (!$eventId instanceof EventId) {
            throw new \InvalidArgumentException('EventCarbonFootprint has no event');
        }

        $results = $this->getResultsService->__invoke(new GetResultsRequest($eventCarbonFootprint->id()), $user);

        return new GetCompensationSummaryResponse(
            $this->compensationCollector->__invoke($eventId, $results->totalEmissions),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GetCompensationSummaryRequest;
    }
}
