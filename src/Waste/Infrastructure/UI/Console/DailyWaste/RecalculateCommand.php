<?php

namespace App\Waste\Infrastructure\UI\Console\DailyWaste;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteRequest;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteService;

#[AsCommand(
    name: 'waste:daily:recalculate',
    description: 'Recalculate daily wastes'
)]
class RecalculateCommand extends Command
{
    public function __construct(
        private readonly MeasurementRepository $measurementRepository,
        private readonly CalculateDailyWasteService $calculateDailyWaste
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Calcular residuos diarios');

        $measurements = $this->measurementRepository->findByFilter(
            new MeasurementFilter(
                limit: 0
            )
        );

        if (count($measurements) === 0) {
            $io->warning("No hay medidas");

            return Command::SUCCESS;
        }

        $progressBar = $io->createProgressBar();

        foreach ($measurements as $measurement) {
            $user = UserMother::fromUsernameAndRoleAndAccount('system', Role::Owner, $measurement->accountId());

            try {
                $this->calculateDailyWaste->__invoke(
                    new CalculateDailyWasteRequest(
                        propertyId: $measurement->propertyId(),
                        wasteId: $measurement->wasteId(),
                        wasteManagerId: $measurement->wasteManagerId(),
                        accountId: $measurement->accountId(),
                        purpose: $measurement->purpose(),
                        day: $measurement->day()
                    ),
                    $user
                );
            } catch (\Exception) {

            }
            
            $progressBar->advance();
        }

        $progressBar->clear();

        $io->success(sprintf("Se han calculado %s medidas", count($measurements)));

        return Command::SUCCESS;
    }
}