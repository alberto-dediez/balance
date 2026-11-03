<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySettings\Get;

use App\Materiality\Application\MaterialitySettings\MaterialitySettingsAuthorizer;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialityDecisionRule;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class GetMaterialitySettingsService
{
    public function __construct(
        private MaterialitySettingsRepository $materialitySettingsRepository,
        private MaterialitySettingsAuthorizer $authorizer,
    ) {}

    public function __invoke(GetMaterialitySettingsRequest $request, User $user): GetMaterialitySettingsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MaterialitySettings(MaterialitySettingsId::generate()), SecurityAction::View, $user);

        $materialitySettings = $this->materialitySettingsRepository->findCurrent();
        $rule = $materialitySettings?->rule() ?? MaterialityDecisionRule::default();

        return new GetMaterialitySettingsResponse(
            relevanceThresholdPercent: $rule->thresholdPercent,
            highMagnitudeForcesInclusion: $rule->highMagnitudeForcesInclusion,
            persisted: null !== $materialitySettings,
        );
    }
}
