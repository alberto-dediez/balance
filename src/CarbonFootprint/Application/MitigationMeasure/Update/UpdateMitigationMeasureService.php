<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Update;

use App\CarbonFootprint\Application\MitigationMeasure\MitigationMeasureAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMitigationMeasureService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationMeasureRepository $mitigationMeasureRepository,
        private MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
        private MitigationMeasureAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMitigationMeasureRequest $request, User $user): UpdateMitigationMeasureResponse
    {
        $mitigationMeasure = $this->mitigationMeasureRepository->find($request->mitigationMeasureId);

        if (null == $mitigationMeasure) {
            throw new \InvalidArgumentException('Mitigation measure not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mitigationMeasure, SecurityAction::Edit, $user);

        $categoryId = $request->categoryId;

        if (null == $categoryId || null == $this->mitigationMeasureCategoryRepository->find($categoryId)) {
            throw new \InvalidArgumentException('Mitigation measure category not found');
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $mitigationMeasure->update(
            name: $request->name,
            description: $request->description,
            categoryId: $categoryId,
            locale: $isDefaultLocale ? null : $locale,
        );
        $mitigationMeasure->syncEffects(MitigationMeasureEffectData::toEffects($request->effects));

        $this->mitigationMeasureRepository->add($mitigationMeasure);

        return new UpdateMitigationMeasureResponse(
            mitigationMeasureId: $mitigationMeasure->id(),
            name: $mitigationMeasure->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMitigationMeasureRequest;
    }
}
