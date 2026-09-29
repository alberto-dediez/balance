<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\Register;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMaterialitySectorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialitySectorRepository $materialitySectorRepository,
        private MaterialitySectorAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMaterialitySectorRequest $request, User $user): RegisterMaterialitySectorResponse
    {
        $id = MaterialitySectorId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MaterialitySector(id: $id), SecurityAction::Register, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $materialitySector = new MaterialitySector($id);
        $materialitySector->register(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->materialitySectorRepository->add($materialitySector);

        return new RegisterMaterialitySectorResponse(
            materialitySectorId: $materialitySector->id(),
            name: $materialitySector->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMaterialitySectorRequest;
    }
}
