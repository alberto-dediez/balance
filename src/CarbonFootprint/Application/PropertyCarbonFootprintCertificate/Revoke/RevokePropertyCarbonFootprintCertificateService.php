<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Revoke;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RevokePropertyCarbonFootprintCertificateService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
    ) {}

    public function __invoke(RevokePropertyCarbonFootprintCertificateRequest $request, User $user): void
    {
        $certificate = $this->certificateRepository->find($request->propertyCarbonFootprintCertificateId);

        if (null === $certificate) {
            throw new \InvalidArgumentException('PropertyCarbonFootprintCertificate not found');
        }

        $certificate->revoke();

        $this->certificateRepository->add($certificate);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RevokePropertyCarbonFootprintCertificateRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RevokePropertyCarbonFootprintCertificateRequest;
    }
}
