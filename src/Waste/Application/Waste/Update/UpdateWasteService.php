<?php

declare(strict_types=1);

namespace App\Waste\Application\Waste\Update;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\WasteRepository;

class UpdateWasteService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private WasteRepository $wasteRepository,
        private LocaleGetter $localeGetter,
        private WasteAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateWasteRequest $request, User $user): UpdateWasteResponse
    {
        $waste = $this->wasteRepository->find($request->wasteId);

        if (null == $waste) {
            throw new \InvalidArgumentException('Waste not found');
        }

        $this->authorizer->denyUnlessCanDoThat($waste, Action::Edit, $user);

        $locale = $this->localeGetter->locale();
        $isDefaultLocale = $locale === $this->localeGetter->default();

        $waste->update(
            name: $request->name,
            isDangerous: $request->isDangerous,
            density: $request->density,
            color: $request->color,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->wasteRepository->add($waste);

        return new UpdateWasteResponse(
            wasteId: $waste->id(),
            name: $waste->name(),
            isDangerous: $waste->isDangerous(),
            density: $waste->density(),
            color: $waste->color(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateWasteRequest;
    }
}
