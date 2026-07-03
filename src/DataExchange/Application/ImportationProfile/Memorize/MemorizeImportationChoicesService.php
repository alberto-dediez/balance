<?php

declare(strict_types=1);

namespace App\DataExchange\Application\ImportationProfile\Memorize;

use App\DataExchange\Application\ImportationProfile\ImportationProfileAuthorizer;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfile;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class MemorizeImportationChoicesService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ImportationRepository $importationRepository,
        private ImportationProfileRepository $importationProfileRepository,
        private ImportationProfileAuthorizer $authorizer,
    ) {}

    public function __invoke(MemorizeImportationChoicesRequest $request, User $user): MemorizeImportationChoicesResponse
    {
        $importation = $this->importationRepository->find($request->importationId);

        if (!$importation instanceof Importation || !$importation->isExecuted()) {
            throw new \RuntimeException('Importation not found or not executed');
        }

        $profile = $this->importationProfileRepository->findByTypeAndAccount($importation->type(), $importation->accountId());

        if (!$profile instanceof ImportationProfile) {
            $profile = new ImportationProfile(id: ImportationProfileId::generate());
            $profile->register($importation->accountId(), $importation->type());
        }

        $this->authorizer->denyUnlessCanDoThat($profile, Action::Edit, $user);

        $fieldsByColumn = [];
        $translationsByColumn = [];

        foreach ($importation->configurations() as $configuration) {
            if (null !== $configuration->field) {
                $fieldsByColumn[$configuration->name] = $configuration->field;
            }

            $translations = [];

            foreach ($configuration->translations as $translation) {
                if (null !== $translation->translation) {
                    $translations[$translation->value] = $translation->translation;
                }
            }

            if (count($translations) > 0) {
                $translationsByColumn[$configuration->name] = $translations;
            }
        }

        $profile->memorize($fieldsByColumn, $translationsByColumn, $importation->defaultValues());

        $this->importationProfileRepository->add($profile);

        return new MemorizeImportationChoicesResponse(
            importationProfileId: $profile->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof MemorizeImportationChoicesRequest;
    }
}
