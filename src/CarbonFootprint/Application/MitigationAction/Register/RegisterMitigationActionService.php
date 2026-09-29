<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Register;

use App\CarbonFootprint\Application\MitigationAction\MitigationEmissionSourcesBuilder;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureTranslation;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMitigationActionService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationActionRepository $mitigationActionRepository,
        private MitigationMeasureRepository $mitigationMeasureRepository,
        private MitigationPlanFootprints $footprints,
        private MitigationEmissionSourcesBuilder $emissionSourcesBuilder,
    ) {}

    public function __invoke(RegisterMitigationActionRequest $request, User $user): RegisterMitigationActionResponse
    {
        $carbonFootprint = $this->footprints->find($request->subjectType, $request->carbonFootprintId);

        $this->footprints->denyUnlessCanDoThat($request->subjectType, $carbonFootprint, SecurityAction::Edit, $user);

        $this->footprints->ensureIsEditable($carbonFootprint);

        $measure = null;

        if (null !== $request->measureId && null === $request->translations) {
            $measure = $this->mitigationMeasureRepository->find($request->measureId);

            if (null == $measure) {
                throw new \InvalidArgumentException('Mitigation measure not found');
            }
        }

        if (null === $request->measureId && '' === trim($request->name)) {
            throw new \InvalidArgumentException('The name of a manual mitigation action is required');
        }

        $accountId = $carbonFootprint->accountId();

        if (!$accountId instanceof AccountId) {
            throw new \RuntimeException('CarbonFootprint has no account');
        }

        $mitigationAction = new MitigationAction(MitigationActionId::generate());

        if (null !== $request->translations) {
            $this->registerImported($mitigationAction, $request, $accountId, $carbonFootprint);
        } elseif ($measure instanceof MitigationMeasure) {
            $this->registerFromMeasure($mitigationAction, $request, $accountId, $carbonFootprint, $measure);
        } else {
            $this->registerManual($mitigationAction, $request, $accountId, $carbonFootprint);
        }

        if (null !== $request->effects) {
            foreach ($request->effects as $effect) {
                $mitigationAction->setEffect(
                    $effect['source'],
                    $effect['itemId'],
                    $effect['itemName'],
                    $effect['reduction'],
                    $effect['minimumReduction'],
                    $effect['maximumReduction'],
                );
            }
        } elseif ($measure instanceof MitigationMeasure) {
            $emissionSources = $this->emissionSourcesBuilder->build($carbonFootprint);

            foreach ($measure->effects() as $measureEffect) {
                foreach ($emissionSources as $emissionSource) {
                    if ($emissionSource->source !== $measureEffect->source()) {
                        continue;
                    }

                    $mitigationAction->setEffect(
                        $emissionSource->source,
                        $emissionSource->itemId,
                        $emissionSource->itemName,
                        $measureEffect->minimumReduction(),
                        $measureEffect->minimumReduction(),
                        $measureEffect->maximumReduction(),
                    );
                }
            }
        }

        $this->mitigationActionRepository->add($mitigationAction);

        return new RegisterMitigationActionResponse(
            mitigationActionId: $mitigationAction->id(),
            name: $mitigationAction->name(),
        );
    }

    private function registerManual(MitigationAction $mitigationAction, RegisterMitigationActionRequest $request, AccountId $accountId, CarbonFootprint $carbonFootprint): void
    {
        $locale = LocaleGetter::locale();

        $mitigationAction->register(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            accountId: $accountId,
            measureId: null,
            name: $request->name,
            description: $request->description,
            baseYear: $request->baseYear ?? $carbonFootprint->calculationYear(),
            targetYear: $request->targetYear,
            locale: LocaleGetter::default() === $locale ? null : $locale,
        );
    }

    private function registerFromMeasure(
        MitigationAction $mitigationAction,
        RegisterMitigationActionRequest $request,
        AccountId $accountId,
        CarbonFootprint $carbonFootprint,
        MitigationMeasure $measure,
    ): void {
        $name = $measure->defaultName();
        $description = $measure->defaultDescription();

        /** @var array<string, array{name: string, description: string}> $translations */
        $translations = [];
        foreach ($measure->translations() as $translation) {
            assert($translation instanceof MitigationMeasureTranslation);
            $translations[$translation->locale()->value] = ['name' => $translation->name(), 'description' => $translation->description()];
        }

        $locale = LocaleGetter::locale();

        if (LocaleGetter::default() === $locale) {
            $name = '' !== trim($request->name) ? $request->name : $name;
            $description = '' !== trim($request->description) ? $request->description : $description;
        } elseif ('' !== trim($request->name) || '' !== trim($request->description)) {
            $current = $translations[$locale->value] ?? ['name' => $name, 'description' => $description];
            $translations[$locale->value] = [
                'name' => '' !== trim($request->name) ? $request->name : $current['name'],
                'description' => '' !== trim($request->description) ? $request->description : $current['description'],
            ];
        }

        $mitigationAction->register(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            accountId: $accountId,
            measureId: $measure->id(),
            name: $name,
            description: $description,
            baseYear: $request->baseYear ?? $carbonFootprint->calculationYear(),
            targetYear: $request->targetYear,
        );

        $this->translate($mitigationAction, $translations);
    }

    private function registerImported(MitigationAction $mitigationAction, RegisterMitigationActionRequest $request, AccountId $accountId, CarbonFootprint $carbonFootprint): void
    {
        $mitigationAction->register(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            accountId: $accountId,
            measureId: $request->measureId,
            name: $request->name,
            description: $request->description,
            baseYear: $request->baseYear ?? $carbonFootprint->calculationYear(),
            targetYear: $request->targetYear,
        );

        $this->translate($mitigationAction, $request->translations ?? []);
    }

    /** @param array<string, array{name: string, description: string}> $translations */
    private function translate(MitigationAction $mitigationAction, array $translations): void
    {
        foreach ($translations as $localeValue => $texts) {
            $locale = Locale::tryFrom($localeValue);

            if (null === $locale) {
                continue;
            }

            $mitigationAction->translate($texts['name'], $texts['description'], $locale);
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMitigationActionRequest;
    }
}
