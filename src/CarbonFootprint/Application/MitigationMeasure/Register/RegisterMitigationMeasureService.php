<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Register;

use App\CarbonFootprint\Application\MitigationMeasure\MitigationMeasureAuthorizer;
use App\CarbonFootprint\Application\MitigationMeasure\Update\MitigationMeasureEffectData;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMitigationMeasureService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationMeasureRepository $mitigationMeasureRepository,
        private MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
        private MitigationMeasureAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMitigationMeasureRequest $request, User $user): RegisterMitigationMeasureResponse
    {
        $id = MitigationMeasureId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MitigationMeasure(id: $id), SecurityAction::Register, $user);

        $categoryId = $request->categoryId;

        if (null == $categoryId || null == $this->mitigationMeasureCategoryRepository->find($categoryId)) {
            throw new \InvalidArgumentException('Mitigation measure category not found');
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $mitigationMeasure = new MitigationMeasure($id);
        $mitigationMeasure->register(
            $request->name,
            $request->description,
            $categoryId,
            $isDefaultLocale ? null : $locale,
        );
        $mitigationMeasure->syncEffects(MitigationMeasureEffectData::toEffects($request->effects));

        $this->mitigationMeasureRepository->add($mitigationMeasure);

        return new RegisterMitigationMeasureResponse(
            mitigationMeasureId: $mitigationMeasure->id(),
            name: $mitigationMeasure->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMitigationMeasureRequest;
    }
}
