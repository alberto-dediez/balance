<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\DeclareIntensityDenominators;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class DeclareIntensityDenominatorsService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(DeclareIntensityDenominatorsRequest $request, User $user): DeclareIntensityDenominatorsResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, Action::Edit, $user);

        $companyCarbonFootprint->declareIntensityDenominators(new IntensityDenominators(
            occupiedRooms: $request->occupiedRooms,
            stays: $request->stays,
            builtAreaM2: $request->builtAreaM2,
            coversServed: $request->coversServed,
            averageHeadcount: $request->averageHeadcount,
            revenueEur: $request->revenueEur,
            customersServed: $request->customersServed,
        ));

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);

        return new DeclareIntensityDenominatorsResponse($companyCarbonFootprint);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof DeclareIntensityDenominatorsRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeclareIntensityDenominatorsRequest;
    }
}
