<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CreateEmissionFactorsYearService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MachineryFuelRepository $machineryFuelRepository,
        private MachineryFuelAuthorizer $authorizer,
    ) {}

    public function __invoke(CreateEmissionFactorsYearRequest $request, User $user): CreateEmissionFactorsYearResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MachineryFuel(id: MachineryFuelId::generate()), SecurityAction::Edit, $user);

        $targetYear = (int) $request->targetYear;
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');
        $emissionFactorGroupId = new EmissionFactorGroupId((string) $request->emissionFactorGroupId);

        if ($targetYear > $currentYear) {
            throw new \InvalidArgumentException('Target year cannot be in the future');
        }

        if ($targetYear === $request->sourceYear) {
            throw new \InvalidArgumentException('Target year must differ from the source year');
        }

        $machineryFuels = $this->machineryFuelRepository->findByFilter(new MachineryFuelFilter(limit: 0));

        foreach ($machineryFuels as $machineryFuel) {
            if ($machineryFuel->emissionFactors()->hasForYearAndGroup($targetYear, $emissionFactorGroupId)) {
                throw EmissionFactorYearAlreadyExistsException::forYear($targetYear);
            }
        }

        foreach ($machineryFuels as $machineryFuel) {
            $sourceFactor = $machineryFuel->emissionFactors()->forYearAndGroup($request->sourceYear, $emissionFactorGroupId);

            if (null === $sourceFactor) {
                continue;
            }

            $machineryFuel->setEmissionFactor(
                $sourceFactor->emissionFactor()->withYear($targetYear),
                $sourceFactor->wellToTankFactor()->withYear($targetYear),
            );

            $this->machineryFuelRepository->add($machineryFuel);
        }

        return new CreateEmissionFactorsYearResponse(targetYear: $targetYear);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CreateEmissionFactorsYearRequest;
    }
}
