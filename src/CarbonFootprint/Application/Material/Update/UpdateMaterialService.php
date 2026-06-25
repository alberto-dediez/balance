<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Update;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMaterialService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialRepository $materialRepository,
        private MaterialAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMaterialRequest $request, User $user): UpdateMaterialResponse
    {
        $material = $this->materialRepository->find($request->materialId);

        if (null == $material) {
            throw new \InvalidArgumentException('Material not found');
        }

        $this->authorizer->denyUnlessCanDoThat($material, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $material->update(
            name: $request->name,
            category: $request->category,
            unit: $request->unit,
            unitWeight: $request->unitWeight,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $material->unit();

        $material->syncEmissionFactors(
            array_map(
                static fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor($unit),
                $request->emissionFactors,
            ),
        );

        $this->materialRepository->add($material);

        return new UpdateMaterialResponse(
            materialId: $material->id(),
            name: $material->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMaterialRequest;
    }
}
