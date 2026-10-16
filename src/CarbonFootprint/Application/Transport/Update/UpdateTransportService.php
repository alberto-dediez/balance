<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Update;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TransportRepository $transportRepository,
        private TransportAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateTransportRequest $request, User $user): UpdateTransportResponse
    {
        $transport = $this->transportRepository->find($request->transportId);

        if (null == $transport) {
            throw new \InvalidArgumentException('Transport not found');
        }

        $this->authorizer->denyUnlessCanDoThat($transport, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $transport->update(
            name: $request->name,
            unit: $request->unit,
            vehicle: $request->vehicle,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $transport->unit();

        $transport->syncEmissionFactors(
            array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    $unit,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            ),
        );

        $this->transportRepository->add($transport);

        return new UpdateTransportResponse(
            transportId: $transport->id(),
            name: $transport->name(),
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
        return $command instanceof UpdateTransportRequest;
    }
}
