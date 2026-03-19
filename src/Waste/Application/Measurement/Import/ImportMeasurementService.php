<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement\Import;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Measurement\Unit;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Component\Translation\TranslatableMessage;

class ImportMeasurementService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MeasurementRepository $measurementRepository,
        private ContainerRepository $containerRepository,
        private PropertyRepository $propertyRepository,
        private WasteRepository $wasteRepository,
        private WasteManagerRepository $wasteManagerRepository,
        private UserRepository $userRepository,
        private MeasurementAuthorizer $authorizer,
    ) {}

    public function __invoke(ImportMeasurementRequest $request, User $user): ImportMeasurementResponse
    {
        [$property, $container, $unit] = $this->getPropertyContainerAndUnitFrom($request, $user);

        $id = MeasurementId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new Measurement(
                id: $id,
                containerId: $container->id(),
                propertyId: $property->id(),
                accountId: $property->accountId(),
                registeredBy: $user->id(),
            ),
            Action::Register,
            $user,
        );

        $measurement = new Measurement($id);
        $measurement
            ->register(
                container: $container,
                quantity: $request->quantity ?? 1,
                load: empty($request->load) ? 0 : ((float) $request->load),
                registeredBy: $user->id(),
                accountId: $property->accountId(),
                justification: $request->justification,
                reference: $request->reference,
                unit: $unit,
                day: empty($request->day) ? null : \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $request->day),
            )
        ;

        $this->measurementRepository->add($measurement);

        return new ImportMeasurementResponse(
            measurementId: $measurement->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ImportMeasurementRequest;
    }

    private function getPropertyContainerAndUnitFrom(ImportMeasurementRequest $request, User $user): array
    {
        if (empty($request->containerId)) {
            $property = empty($request->propertyId) ? null : $this->propertyRepository->find(new PropertyId($request->propertyId));
            $waste = empty($request->wasteId) ? null : $this->wasteRepository->find(new WasteId($request->wasteId));
            $wasteManager = empty($request->wasteManagerId) ? null : $this->wasteManagerRepository->find(new WasteManagerId($request->wasteManagerId));
            $purpose = empty($request->purpose) ? null : Purpose::tryFrom($request->purpose);

            if (!$property instanceof Property) {
                throw new \InvalidArgumentException('Debe indicar una propiedad para la medición');
            }

            if (!$waste instanceof Waste) {
                throw new \InvalidArgumentException('Debe indicar el residuo para la medición');
            }

            if (!$wasteManager instanceof WasteManager) {
                throw new \InvalidArgumentException('Debe indicar el gestor de residuos para la medición');
            }

            if (null === $purpose) {
                throw new \InvalidArgumentException('Debe indicar el destino de la medición');
            }

            $container = new Container(
                id: ContainerId::generate(),
                accountId: $user->accountId(),
                propertyId: $property->id(),
                wasteId: $waste->id(),
                purpose: $purpose,
                measurementMethod: MeasurementMethod::Weight,
                volume: (string) 0,
                wasteManagerId: $wasteManager->id(),
            );
        } else {
            $container = $this->containerRepository->find(new ContainerId($request->containerId));

            if (!$container instanceof Container) {
                throw new \InvalidArgumentException('El contenedor indicado no existe');
            }

            $property = $this->propertyRepository->find($container->propertyId());

            if (!$property instanceof Property) {
                throw new \InvalidArgumentException('Debe indicar una propiedad para la medición');
            }
        }

        $unit = Unit::tryFrom($request->unit);

        if (null === $unit) {
            throw new \InvalidArgumentException('Debe indicar la unidad de la medición');
        }

        return [$property, $container, $unit];
    }
}
