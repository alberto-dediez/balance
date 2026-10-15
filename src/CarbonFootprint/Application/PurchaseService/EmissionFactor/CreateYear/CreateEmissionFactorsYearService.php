<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
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
        private PurchaseServiceRepository $purchaseServiceRepository,
        private PurchaseServiceAuthorizer $authorizer,
    ) {}

    public function __invoke(CreateEmissionFactorsYearRequest $request, User $user): CreateEmissionFactorsYearResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new PurchaseService(id: PurchaseServiceId::generate()), SecurityAction::Edit, $user);

        $targetYear = (int) $request->targetYear;
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');
        $emissionFactorGroupId = new EmissionFactorGroupId((string) $request->emissionFactorGroupId);

        if ($targetYear > $currentYear) {
            throw new \InvalidArgumentException('Target year cannot be in the future');
        }

        if ($targetYear === $request->sourceYear) {
            throw new \InvalidArgumentException('Target year must differ from the source year');
        }

        $purchaseServices = $this->purchaseServiceRepository->findByFilter(new PurchaseServiceFilter(limit: 0));

        foreach ($purchaseServices as $purchaseService) {
            if ($purchaseService->emissionFactors()->hasForYearAndGroup($targetYear, $emissionFactorGroupId)) {
                throw EmissionFactorYearAlreadyExistsException::forYear($targetYear);
            }
        }

        foreach ($purchaseServices as $purchaseService) {
            $sourceFactor = $purchaseService->emissionFactorFor($request->sourceYear, $emissionFactorGroupId);

            if (null === $sourceFactor) {
                continue;
            }

            $purchaseService->setEmissionFactor($sourceFactor->emissionFactor()->withYear($targetYear));

            $this->purchaseServiceRepository->add($purchaseService);
        }

        return new CreateEmissionFactorsYearResponse(targetYear: $targetYear);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CreateEmissionFactorsYearRequest;
    }
}
