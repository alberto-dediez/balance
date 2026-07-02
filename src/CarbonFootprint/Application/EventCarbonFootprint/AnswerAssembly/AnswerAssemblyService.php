<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\AnswerAssembly;

use App\CarbonFootprint\Application\Activity\Sync\PhaseActivitiesSynchronizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class AnswerAssemblyService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private PhaseActivitiesSynchronizer $activitiesSynchronizer,
    ) {}

    public function __invoke(AnswerAssemblyRequest $request, User $user): void
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $this->activitiesSynchronizer->sync($eventCarbonFootprint, Phase::Assembly, Source::FixedFacilities, $request->fixedFacilitiesRows, $user);
        $this->activitiesSynchronizer->sync($eventCarbonFootprint, Phase::Assembly, Source::FreightTransport, $request->freightTransportRows, $user);
        $this->activitiesSynchronizer->sync($eventCarbonFootprint, Phase::Assembly, Source::MachineryOperation, $request->machineryOperationRows, $user);
        $this->activitiesSynchronizer->sync($eventCarbonFootprint, Phase::Assembly, Source::BuildingElectricity, $request->buildingElectricityRows, $user);
        $this->activitiesSynchronizer->sync($eventCarbonFootprint, Phase::Assembly, Source::SuppliersAndStaffAccommodation, $request->accommodationRows, $user);
        $this->activitiesSynchronizer->sync($eventCarbonFootprint, Phase::Assembly, Source::SuppliersAndStaffMobility, $request->mobilityRows, $user);
        $this->activitiesSynchronizer->sync($eventCarbonFootprint, Phase::Assembly, Source::WasteManagement, $request->wasteManagementRows, $user);

        $eventCarbonFootprint->answerAssembly();

        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof AnswerAssemblyRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AnswerAssemblyRequest;
    }
}
