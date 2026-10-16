<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Register;

use App\CarbonFootprint\Application\EconomicSector\ClassificationCodeNormalizer;
use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorCodeAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterEconomicSectorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EconomicSectorRepository $economicSectorRepository,
        private EconomicSectorAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterEconomicSectorRequest $request, User $user): RegisterEconomicSectorResponse
    {
        $id = EconomicSectorId::generate();

        $this->authorizer->denyUnlessCanDoThat(new EconomicSector(id: $id), SecurityAction::List, $user);

        $classificationCode = ClassificationCodeNormalizer::normalize($request->classificationCode);

        if (null !== $classificationCode && null !== $this->economicSectorRepository->findByClassificationCode($classificationCode)) {
            throw new EconomicSectorCodeAlreadyExistsException($classificationCode);
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $economicSector = new EconomicSector($id);
        $economicSector->register(
            $request->name,
            $request->unit,
            $classificationCode,
            $isDefaultLocale ? null : $locale,
        );

        $this->economicSectorRepository->add($economicSector);

        return new RegisterEconomicSectorResponse(
            economicSectorId: $economicSector->id(),
            name: $economicSector->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEconomicSectorRequest;
    }
}
