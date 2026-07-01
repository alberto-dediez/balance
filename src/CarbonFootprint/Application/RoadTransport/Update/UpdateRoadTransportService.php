<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Update;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateRoadTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RoadTransportRepository $roadTransportRepository,
        private RoadTransportAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateRoadTransportRequest $request, User $user): UpdateRoadTransportResponse
    {
        $roadTransport = $this->roadTransportRepository->find($request->roadTransportId);

        if (null == $roadTransport) {
            throw new \InvalidArgumentException('Road transport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($roadTransport, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $roadTransport->update(
            name: $request->name,
            unit: $request->unit,
            vehicle: $request->vehicle,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $roadTransport->unit();

        $roadTransport->syncEmissionFactors(
            array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    $unit,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            ),
        );

        $this->roadTransportRepository->add($roadTransport);

        return new UpdateRoadTransportResponse(
            roadTransportId: $roadTransport->id(),
            name: $roadTransport->name(),
        );
    }

    private function resolveEmissionFactorGroup(?string $emissionFactorGroupId): EmissionFactorGroup
    {
        $emissionFactorGroup = null !== $emissionFactorGroupId
            ? $this->emissionFactorGroupRepository->find(new EmissionFactorGroupId($emissionFactorGroupId))
            : null;

        if (null === $emissionFactorGroup) {
            throw new \InvalidArgumentException('Emission factor group not found');
        }

        return $emissionFactorGroup;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateRoadTransportRequest;
    }
}
