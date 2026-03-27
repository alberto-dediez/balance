<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Console\DailyWaste;

use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteRequest;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteService;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'waste:daily:recalculate',
    description: 'Recalculate daily wastes',
)]
class RecalculateCommand extends Command
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly CalculateDailyWasteService $calculateDailyWaste,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Calcular residuos diarios');

        $measurements = $this->measurementRepository->findByFilter(
            new MeasurementFilter(
                limit: 0,
            ),
        );

        if (0 === count($measurements)) {
            $io->warning('No hay medidas');

            return Command::SUCCESS;
        }

        $progressBar = $io->createProgressBar();

        foreach ($measurements as $measurement) {
            $measurementAccountId = $measurement->accountId();
            $measurementPropertyId = $measurement->propertyId();
            $measurementWasteId = $measurement->wasteId();
            $measurementWasteManagerId = $measurement->wasteManagerId();
            $measurementPurpose = $measurement->purpose();
            $measurementDay = $measurement->day();
            assert(null !== $measurementAccountId);
            assert(null !== $measurementPropertyId);
            assert(null !== $measurementWasteId);
            assert(null !== $measurementWasteManagerId);
            $user = UserMother::fromUsernameAndRoleAndAccount('system', Role::Owner, $measurementAccountId);

            try {
                $this->calculateDailyWaste->__invoke(
                    new CalculateDailyWasteRequest(
                        propertyId: $measurementPropertyId,
                        wasteId: $measurementWasteId,
                        wasteManagerId: $measurementWasteManagerId,
                        accountId: $measurementAccountId,
                        purpose: $measurementPurpose,
                        day: $measurementDay,
                    ),
                    $user,
                );
            } catch (\Exception) {

            }

            $progressBar->advance();
        }

        $progressBar->clear();

        $io->success(sprintf('Se han calculado %s medidas', count($measurements)));

        return Command::SUCCESS;
    }
}
