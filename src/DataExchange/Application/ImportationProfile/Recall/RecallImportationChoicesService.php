<?php

declare(strict_types=1);

namespace App\DataExchange\Application\ImportationProfile\Recall;

use App\DataExchange\Application\ImportationProfile\ImportationProfileAuthorizer;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfile;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RecallImportationChoicesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ImportationRepository $importationRepository,
        private ImportationProfileRepository $importationProfileRepository,
        private ImportationProfileAuthorizer $authorizer,
    ) {}

    public function __invoke(RecallImportationChoicesRequest $request, User $user): RecallImportationChoicesResponse
    {
        $importation = $this->importationRepository->find($request->importationId);

        if (!$importation instanceof Importation) {
            throw new \RuntimeException('Importation not found');
        }

        $profile = $this->importationProfileRepository->findByTypeAndAccount($importation->type(), $importation->accountId());

        if (!$profile instanceof ImportationProfile) {
            return new RecallImportationChoicesResponse(
                configurations: $importation->configurations(),
                defaultValues: $importation->defaultValues(),
            );
        }

        $this->authorizer->denyUnlessCanDoThat($profile, Action::View, $user);

        return new RecallImportationChoicesResponse(
            configurations: $profile->applyToConfigurations($importation->configurations()),
            defaultValues: $profile->defaultValues(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RecallImportationChoicesRequest;
    }
}
