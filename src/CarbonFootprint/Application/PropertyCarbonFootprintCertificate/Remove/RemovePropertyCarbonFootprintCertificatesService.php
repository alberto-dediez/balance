<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Remove;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStorage;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemovePropertyCarbonFootprintCertificatesService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
        private PropertyCarbonFootprintCertificateStorage $storage,
    ) {}

    public function __invoke(RemovePropertyCarbonFootprintCertificatesRequest $request, User $user): void
    {
        foreach ($this->certificateRepository->findByPropertyCarbonFootprintId($request->propertyCarbonFootprintId) as $certificate) {
            foreach ($certificate->files() as $path) {
                $this->storage->delete($path);
            }

            $certificate->remove();

            $this->certificateRepository->remove($certificate);
        }
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RemovePropertyCarbonFootprintCertificatesRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemovePropertyCarbonFootprintCertificatesRequest;
    }
}
