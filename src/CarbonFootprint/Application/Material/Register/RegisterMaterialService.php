<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Register;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMaterialService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialRepository $materialRepository,
        private MaterialAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMaterialRequest $request, User $user): RegisterMaterialResponse
    {
        $id = MaterialId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Material(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $material = new Material($id);
        $material->register(
            $request->name,
            $request->category,
            $request->unit,
            $isDefaultLocale ? null : $locale,
        );

        $this->materialRepository->add($material);

        return new RegisterMaterialResponse(
            materialId: $material->id(),
            name: $material->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMaterialRequest;
    }
}
