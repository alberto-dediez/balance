<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Download;

use App\DataExchange\Application\Exportation\ExportationAuthorizer;
use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\DataExchange\Domain\Model\Exportation\ExportStorage;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use App\DataExchange\Domain\Model\Exportation\StoredFile;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class DownloadExportationService
{
    public function __construct(
        private readonly ExportationRepository $exportationRepository,
        private readonly ExportStorage $exportStorage,
        private readonly ExportationAuthorizer $authorizer,
    ) {}

    public function __invoke(DownloadExportationRequest $request, User $user): DownloadExportationResponse
    {
        $exportation = $this->exportationRepository->find($request->exportationId);

        if (!$exportation instanceof Exportation) {
            throw new NotExportedException('Exportation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($exportation, Action::View, $user);

        if (!$exportation->isCompleted()) {
            throw new NotExportedException('Exportation is not ready');
        }

        if ($exportation->isExpired(new \DateTimeImmutable('NOW'))) {
            throw new NotExportedException('Exportation has expired');
        }

        $file = $exportation->file();

        if (!$file instanceof StoredFile || !$this->exportStorage->exists($file)) {
            throw new NotExportedException('Exportation file is no longer available');
        }

        return new DownloadExportationResponse(
            stream: $this->exportStorage->read($file),
            filename: sprintf('%s-%s.%s', $exportation->type(), $exportation->id(), $exportation->format()->extension()),
            format: $exportation->format(),
        );
    }
}
