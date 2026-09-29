<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\Update;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMaterialitySectorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialitySectorRepository $materialitySectorRepository,
        private MaterialitySectorAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMaterialitySectorRequest $request, User $user): UpdateMaterialitySectorResponse
    {
        $materialitySector = $this->materialitySectorRepository->find($request->materialitySectorId);

        if (null == $materialitySector) {
            throw new MaterialitySectorNotFoundException($request->materialitySectorId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialitySector, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $materialitySector->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->materialitySectorRepository->add($materialitySector);

        return new UpdateMaterialitySectorResponse(
            materialitySectorId: $materialitySector->id(),
            name: $materialitySector->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMaterialitySectorRequest;
    }
}
