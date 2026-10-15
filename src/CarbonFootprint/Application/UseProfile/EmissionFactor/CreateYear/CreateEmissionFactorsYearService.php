<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
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
        private UseProfileRepository $useProfileRepository,
        private UseProfileAuthorizer $authorizer,
    ) {}

    public function __invoke(CreateEmissionFactorsYearRequest $request, User $user): CreateEmissionFactorsYearResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new UseProfile(id: UseProfileId::generate()), SecurityAction::Edit, $user);

        $targetYear = (int) $request->targetYear;
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');
        $emissionFactorGroupId = new EmissionFactorGroupId((string) $request->emissionFactorGroupId);

        if ($targetYear > $currentYear) {
            throw new \InvalidArgumentException('Target year cannot be in the future');
        }

        if ($targetYear === $request->sourceYear) {
            throw new \InvalidArgumentException('Target year must differ from the source year');
        }

        $useProfiles = $this->useProfileRepository->findByFilter(new UseProfileFilter(limit: 0));

        foreach ($useProfiles as $useProfile) {
            if ($useProfile->emissionFactors()->hasForYearAndGroup($targetYear, $emissionFactorGroupId)) {
                throw EmissionFactorYearAlreadyExistsException::forYear($targetYear);
            }
        }

        foreach ($useProfiles as $useProfile) {
            $sourceFactor = $useProfile->emissionFactorFor($request->sourceYear, $emissionFactorGroupId);

            if (null === $sourceFactor) {
                continue;
            }

            $useProfile->setEmissionFactor($sourceFactor->emissionFactor()->withYear($targetYear));

            $this->useProfileRepository->add($useProfile);
        }

        return new CreateEmissionFactorsYearResponse(targetYear: $targetYear);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CreateEmissionFactorsYearRequest;
    }
}
