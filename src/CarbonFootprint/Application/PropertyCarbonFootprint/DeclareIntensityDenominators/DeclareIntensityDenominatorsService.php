<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\DeclareIntensityDenominators;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
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
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(DeclareIntensityDenominatorsRequest $request, User $user): DeclareIntensityDenominatorsResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, Action::Edit, $user);

        $propertyCarbonFootprint->declareIntensityDenominators(new IntensityDenominators(
            occupiedRooms: $request->occupiedRooms,
            stays: $request->stays,
            builtAreaM2: $request->builtAreaM2,
            coversServed: $request->coversServed,
            averageHeadcount: $request->averageHeadcount,
            revenueEur: $request->revenueEur,
            customersServed: $request->customersServed,
        ));

        $this->propertyCarbonFootprintRepository->add($propertyCarbonFootprint);

        return new DeclareIntensityDenominatorsResponse($propertyCarbonFootprint);
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
