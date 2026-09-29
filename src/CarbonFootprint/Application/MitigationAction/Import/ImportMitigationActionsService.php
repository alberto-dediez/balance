<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Import;

use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Application\MitigationAction\Register\RegisterMitigationActionRequest;
use App\CarbonFootprint\Application\MitigationAction\Register\RegisterMitigationActionService;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionTranslation;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ImportMitigationActionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationActionRepository $mitigationActionRepository,
        private MitigationPlanFootprints $footprints,
        private RegisterMitigationActionService $registerMitigationAction,
    ) {}

    public function __invoke(ImportMitigationActionsRequest $request, User $user): ImportMitigationActionsResponse
    {
        $carbonFootprint = $this->footprints->find($request->subjectType, $request->carbonFootprintId);

        $this->footprints->denyUnlessCanDoThat($request->subjectType, $carbonFootprint, SecurityAction::Edit, $user);

        $this->footprints->ensureIsEditable($carbonFootprint);

        $currentActions = $this->mitigationActionRepository->findByFilter(new MitigationActionFilter(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            limit: 0,
        ));

        if ($request->onlyIfEmpty && [] !== $currentActions) {
            return new ImportMitigationActionsResponse(0);
        }

        foreach ($currentActions as $currentAction) {
            $currentAction->remove();
            $this->mitigationActionRepository->add($currentAction);
        }

        $previous = $this->footprints->previous($request->subjectType, $carbonFootprint);

        if (null === $previous) {
            return new ImportMitigationActionsResponse(0);
        }

        $previousActions = $this->mitigationActionRepository->findByFilter(new MitigationActionFilter(
            subjectType: $request->subjectType,
            carbonFootprintId: (string) $previous->id(),
            limit: 0,
        ));

        $imported = 0;

        foreach ($previousActions as $previousAction) {
            if ($previousAction->targetYear() <= $carbonFootprint->calculationYear()) {
                continue;
            }

            ($this->registerMitigationAction)($this->registerRequestFrom($previousAction, $request), $user);

            ++$imported;
        }

        return new ImportMitigationActionsResponse($imported);
    }

    private function registerRequestFrom(MitigationAction $previousAction, ImportMitigationActionsRequest $request): RegisterMitigationActionRequest
    {
        $translations = [];
        foreach ($previousAction->translations() as $translation) {
            assert($translation instanceof MitigationActionTranslation);
            $translations[$translation->locale()->value] = ['name' => $translation->name(), 'description' => $translation->description()];
        }

        $effects = [];
        foreach ($previousAction->effects() as $effect) {
            $effects[] = [
                'source' => $effect->source(),
                'itemId' => $effect->itemId(),
                'itemName' => $effect->itemName(),
                'reduction' => $effect->reduction(),
                'minimumReduction' => $effect->minimumReduction(),
                'maximumReduction' => $effect->maximumReduction(),
            ];
        }

        return new RegisterMitigationActionRequest(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            measureId: $previousAction->measureId(),
            name: $previousAction->defaultName(),
            description: $previousAction->defaultDescription(),
            targetYear: $previousAction->targetYear(),
            baseYear: $previousAction->baseYear(),
            effects: $effects,
            translations: $translations,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ImportMitigationActionsRequest;
    }
}
