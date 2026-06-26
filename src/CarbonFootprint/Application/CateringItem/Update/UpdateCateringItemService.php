<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Update;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateCateringItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CateringItemRepository $cateringItemRepository,
        private CateringItemAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateCateringItemRequest $request, User $user): UpdateCateringItemResponse
    {
        $cateringItem = $this->cateringItemRepository->find($request->cateringItemId);

        if (null == $cateringItem) {
            throw new \InvalidArgumentException('CateringItem not found');
        }

        $this->authorizer->denyUnlessCanDoThat($cateringItem, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $cateringItem->update(
            name: $request->name,
            category: $request->category,
            unit: $request->unit,
            unitWeight: $request->unitWeight,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $cateringItem->unit();

        $cateringItem->syncEmissionFactors(
            array_map(
                static fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor($unit),
                $request->emissionFactors,
            ),
        );

        $this->cateringItemRepository->add($cateringItem);

        return new UpdateCateringItemResponse(
            cateringItemId: $cateringItem->id(),
            name: $cateringItem->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateCateringItemRequest;
    }
}
