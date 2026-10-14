<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Execute;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportationTypeFactory;
use App\DataExchange\Domain\Model\Exportation\ExportStorage;
use App\DataExchange\Domain\Model\Exportation\ExportWriter;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExecuteExportationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly ExportationRepository $exportationRepository,
        private readonly ExportationTypeFactory $exportationTypeFactory,
        private readonly ExportStorage $exportStorage,
        private readonly ExportWriter $exportWriter,
        private readonly TranslatorInterface $translator,
    ) {}

    public function __invoke(ExecuteExportationRequest $request, User $user): ExecuteExportationResponse
    {
        $exportation = $this->exportationRepository->find($request->exportationId);

        if (!$exportation instanceof Exportation) {
            throw new \RuntimeException('Exportation not found');
        }

        $exportationType = $this->exportationTypeFactory->createFromCode($exportation->type());

        if (!$exportationType instanceof ExportationType) {
            throw new \RuntimeException('Export type not found');
        }

        $exportation->startExecution();
        $this->exportationRepository->add($exportation);

        $temporaryPath = (string) tempnam(sys_get_temp_dir(), 'export_');

        try {
            $this->generate($exportation, $exportationType, $temporaryPath);

            $storedFile = $this->exportStorage->store(
                $exportation->id(),
                $exportation->accountId(),
                $exportation->format()->extension(),
                $temporaryPath,
            );

            $exportation->completeExecution($storedFile);
            $this->exportationRepository->add($exportation);

            $success = true;
        } catch (\Throwable $exception) {
            $exportation->failExecution($exception->getMessage());
            $this->exportationRepository->add($exportation);

            $success = false;
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }

        return new ExecuteExportationResponse(
            exportationId: $exportation->id(),
            success: $success,
        );
    }

    private function generate(Exportation $exportation, ExportationType $exportationType, string $temporaryPath): void
    {
        $columns = $this->orderedColumns($exportation, $exportationType);

        $filter = $exportationType->hydrateFilter($exportation->filterCriteria());

        $name = $exportationType->name();

        $title = $name instanceof TranslatableMessage ? $name->trans($this->translator) : $name;

        $this->exportWriter->open($temporaryPath, $exportation->format(), $title);

        try {
            $this->exportWriter->writeRow(array_values($columns));

            foreach ($exportationType->rows($filter, array_keys($columns)) as $row) {
                $cells = [];
                foreach (array_keys($columns) as $key) {
                    $cells[] = $row[$key] ?? null;
                }

                $this->exportWriter->writeRow($cells);
            }
        } finally {
            $this->exportWriter->close();
        }
    }

    /**
     * Builds an ordered map of selected column key => translated header label,
     * preserving the order declared by the export type.
     *
     * @return array<string, string>
     */
    private function orderedColumns(Exportation $exportation, ExportationType $exportationType): array
    {
        $selected = $exportation->columns();

        $columns = [];
        foreach ($exportationType->columnDefinitions() as $definition) {
            if (!in_array($definition->key, $selected, true)) {
                continue;
            }

            $columns[$definition->key] = $definition->label instanceof TranslatableMessage
                ? $definition->label->trans($this->translator)
                : $definition->label;
        }

        return $columns;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExecuteExportationRequest;
    }
}
