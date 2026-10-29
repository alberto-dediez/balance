<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertiesSynchronizer;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CompleteCompanyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private SyncOrganizationalEmployeeActivitiesService $syncEmployeeActivities,
        private NonConsolidatedPropertiesSynchronizer $nonConsolidatedPropertiesSynchronizer,
    ) {}

    public function __invoke(CompleteCompanyCarbonFootprintRequest $request, User $user): void
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);

        $companyCarbonFootprint->ensureCanBeCompleted();

        ($this->syncEmployeeActivities)(
            new SyncOrganizationalEmployeeActivitiesRequest(CarbonFootprintReportSubjectType::Company, (string) $companyCarbonFootprint->id()),
            $user,
        );

        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId) ?? $companyCarbonFootprint;

        if (!$companyCarbonFootprint->isStageExcluded(Scope::Scope3)) {
            $this->nonConsolidatedPropertiesSynchronizer->sync($companyCarbonFootprint, null);
        }

        $total = $this->breakdownBuilder->build($companyCarbonFootprint, Scope::cases())->total;

        $companyCarbonFootprint->complete($total);

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof CompleteCompanyCarbonFootprintRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CompleteCompanyCarbonFootprintRequest;
    }
}
