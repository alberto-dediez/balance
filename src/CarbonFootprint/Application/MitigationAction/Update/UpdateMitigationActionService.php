<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Update;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionAuthorizer;
use App\CarbonFootprint\Application\MitigationAction\MitigationEmissionSourcesBuilder;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMitigationActionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationActionRepository $mitigationActionRepository,
        private MitigationActionAuthorizer $authorizer,
        private MitigationPlanFootprints $footprints,
        private MitigationEmissionSourcesBuilder $emissionSourcesBuilder,
    ) {}

    public function __invoke(UpdateMitigationActionRequest $request, User $user): UpdateMitigationActionResponse
    {
        $mitigationAction = $this->mitigationActionRepository->find($request->mitigationActionId);

        if (null == $mitigationAction) {
            throw new \InvalidArgumentException('Mitigation action not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mitigationAction, SecurityAction::Edit, $user);

        $carbonFootprint = $this->footprints->find($mitigationAction->subjectType(), $mitigationAction->carbonFootprintId());

        $this->footprints->ensureIsEditable($carbonFootprint);

        $locale = LocaleGetter::locale();

        $mitigationAction->update(
            name: $request->name,
            description: $request->description,
            targetYear: $request->targetYear,
            locale: LocaleGetter::default() === $locale ? null : $locale,
        );

        if ($mitigationAction->isManual()) {
            $emissionSources = [];
            foreach ($this->emissionSourcesBuilder->build($carbonFootprint) as $emissionSource) {
                $emissionSources[$emissionSource->key()] = $emissionSource;
            }

            $effects = [];
            foreach ($request->effects as $effect) {
                $emissionSource = $emissionSources[$effect->source->value . '|' . $effect->itemId] ?? null;

                if (null === $emissionSource) {
                    throw new \InvalidArgumentException('The emission source of the effect does not exist in the carbon footprint');
                }

                $effects[] = [
                    'source' => $effect->source,
                    'itemId' => $effect->itemId,
                    'itemName' => $emissionSource->itemName,
                    'reduction' => $effect->reduction,
                ];
            }

            $mitigationAction->syncEffects($effects);
        } else {
            foreach ($request->effects as $effect) {
                $mitigationAction->changeReduction($effect->source, $effect->itemId, $effect->reduction);
            }
        }

        $this->mitigationActionRepository->add($mitigationAction);

        return new UpdateMitigationActionResponse(
            mitigationActionId: $mitigationAction->id(),
            name: $mitigationAction->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMitigationActionRequest;
    }
}
