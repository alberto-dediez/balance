<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Update;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMerchandisingItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MerchandisingItemRepository $merchandisingItemRepository,
        private MerchandisingItemAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMerchandisingItemRequest $request, User $user): UpdateMerchandisingItemResponse
    {
        $merchandisingItem = $this->merchandisingItemRepository->find($request->merchandisingItemId);

        if (null == $merchandisingItem) {
            throw new \InvalidArgumentException('MerchandisingItem not found');
        }

        $this->authorizer->denyUnlessCanDoThat($merchandisingItem, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $merchandisingItem->update(
            name: $request->name,
            category: $request->category,
            unit: $request->unit,
            unitWeight: $request->unitWeight,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $merchandisingItem->unit();

        $merchandisingItem->syncEmissionFactors(
            array_values(array_map(
                static fn(EmissionFactorData $data): array => [
                    'new' => $data->newEmissionFactor($unit),
                    'recycled' => $data->recycledEmissionFactor($unit),
                ],
                $request->emissionFactors,
            )),
        );

        $this->merchandisingItemRepository->add($merchandisingItem);

        return new UpdateMerchandisingItemResponse(
            merchandisingItemId: $merchandisingItem->id(),
            name: $merchandisingItem->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMerchandisingItemRequest;
    }
}
