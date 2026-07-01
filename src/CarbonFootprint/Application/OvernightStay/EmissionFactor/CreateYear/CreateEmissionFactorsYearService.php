<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
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
        private OvernightStayRepository $overnightStayRepository,
        private OvernightStayAuthorizer $authorizer,
    ) {}

    public function __invoke(CreateEmissionFactorsYearRequest $request, User $user): CreateEmissionFactorsYearResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new OvernightStay(id: OvernightStayId::generate()), SecurityAction::Edit, $user);

        $targetYear = (int) $request->targetYear;
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');
        $emissionFactorGroupId = new EmissionFactorGroupId((string) $request->emissionFactorGroupId);

        if ($targetYear > $currentYear) {
            throw new \InvalidArgumentException('Target year cannot be in the future');
        }

        if ($targetYear === $request->sourceYear) {
            throw new \InvalidArgumentException('Target year must differ from the source year');
        }

        $overnightStays = $this->overnightStayRepository->findByFilter(new OvernightStayFilter(limit: 0));

        foreach ($overnightStays as $overnightStay) {
            if ($overnightStay->emissionFactors()->hasForYearAndGroup($targetYear, $emissionFactorGroupId)) {
                throw EmissionFactorYearAlreadyExistsException::forYear($targetYear);
            }
        }

        foreach ($overnightStays as $overnightStay) {
            $sourceFactor = $overnightStay->emissionFactorFor($request->sourceYear, $emissionFactorGroupId);

            if (null === $sourceFactor) {
                continue;
            }

            $overnightStay->setEmissionFactor($sourceFactor->withYear($targetYear));

            $this->overnightStayRepository->add($overnightStay);
        }

        return new CreateEmissionFactorsYearResponse(targetYear: $targetYear);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CreateEmissionFactorsYearRequest;
    }
}
