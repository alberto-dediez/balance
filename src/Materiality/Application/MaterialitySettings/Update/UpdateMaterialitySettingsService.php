<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySettings\Update;

use App\Materiality\Application\MaterialitySettings\MaterialitySettingsAuthorizer;
use App\Materiality\Domain\Model\MaterialitySettings\InvalidRelevanceThresholdException;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateMaterialitySettingsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialitySettingsRepository $materialitySettingsRepository,
        private MaterialitySettingsAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMaterialitySettingsRequest $request, User $user): UpdateMaterialitySettingsResponse
    {
        $materialitySettings = $this->materialitySettingsRepository->findCurrent();

        $this->authorizer->denyUnlessCanDoThat($materialitySettings ?? new MaterialitySettings(MaterialitySettingsId::generate()), SecurityAction::Edit, $user);

        if (null === $request->relevanceThresholdPercent) {
            throw new InvalidRelevanceThresholdException(0);
        }

        if (null === $materialitySettings) {
            $materialitySettings = new MaterialitySettings(MaterialitySettingsId::generate());
            $materialitySettings->register($request->relevanceThresholdPercent, $request->highMagnitudeForcesInclusion);
        } else {
            $materialitySettings->update($request->relevanceThresholdPercent, $request->highMagnitudeForcesInclusion);
        }

        $this->materialitySettingsRepository->add($materialitySettings);

        return new UpdateMaterialitySettingsResponse(
            materialitySettingsId: $materialitySettings->id(),
            relevanceThresholdPercent: $materialitySettings->relevanceThresholdPercent(),
            highMagnitudeForcesInclusion: $materialitySettings->highMagnitudeForcesInclusion(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMaterialitySettingsRequest;
    }
}
