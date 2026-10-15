<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\EmissionFactor\Update;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateEmissionFactorsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UseProfileRepository $useProfileRepository,
        private UseProfileAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new UseProfile(id: UseProfileId::generate()), SecurityAction::Edit, $user);

        foreach ($request->rows as $row) {
            $useProfile = $this->useProfileRepository->find($row->useProfileId());

            if (null === $useProfile) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($useProfile, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $useProfile->removeEmissionFactor($request->year, $row->emissionFactorGroupId());
            } else {
                $emissionFactorGroup = $this->resolveEmissionFactorGroup($row->emissionFactorGroupId);

                $useProfile->setEmissionFactor(
                    $row->toEmissionFactor($request->year, $useProfile->unit(), $emissionFactorGroup),
                );
            }

            $this->useProfileRepository->add($useProfile);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
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
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
