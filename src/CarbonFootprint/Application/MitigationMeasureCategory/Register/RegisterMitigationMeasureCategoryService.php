<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\Register;

use App\CarbonFootprint\Application\MitigationMeasureCategory\MitigationMeasureCategoryAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMitigationMeasureCategoryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
        private MitigationMeasureCategoryAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMitigationMeasureCategoryRequest $request, User $user): RegisterMitigationMeasureCategoryResponse
    {
        $id = MitigationMeasureCategoryId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MitigationMeasureCategory(id: $id), SecurityAction::Register, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $mitigationMeasureCategory = new MitigationMeasureCategory($id);
        $mitigationMeasureCategory->register(
            $request->name,
            $isDefaultLocale ? null : $locale,
        );

        $this->mitigationMeasureCategoryRepository->add($mitigationMeasureCategory);

        return new RegisterMitigationMeasureCategoryResponse(
            mitigationMeasureCategoryId: $mitigationMeasureCategory->id(),
            name: $mitigationMeasureCategory->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMitigationMeasureCategoryRequest;
    }
}
