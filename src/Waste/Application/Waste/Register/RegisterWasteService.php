<?php

namespace App\Waste\Application\Waste\Register;

use App\Security\Application\Action;
use App\Waste\Domain\Model\Waste\Waste;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\WasteRepository;

class RegisterWasteService
{
    public function __construct(
        private WasteRepository $wasteRepository, 
        private LocaleGetter $localeGetter, 
        private WasteAuthorizer $authorizer
    ) {
        
    }

    public function __invoke(RegisterWasteRequest $request, User $user): RegisterWasteResponse
    {
        $id = WasteId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Waste(id: $id), Action::List, $user);

        $locale = $this->localeGetter->locale();
        $isDefaultLocale = $this->localeGetter->default() === $locale;

        $waste = new Waste($id);
        $waste->register(
            $request->name, 
            $request->isDangerous,
            $request->density,
            $request->color,
            $isDefaultLocale ? null : $locale
        );

        $this->wasteRepository->add($waste);

        return new RegisterWasteResponse(
            wasteId: $waste->id(), 
            name: $waste->name(),
            isDangerous: $waste->isDangerous(),
            density: $waste->density(),
            color: $waste->color()
        );
    }
}