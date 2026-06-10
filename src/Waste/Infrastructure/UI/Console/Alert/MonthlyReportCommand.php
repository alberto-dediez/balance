<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Console\Alert;

use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\Alert\MonthlyReport\MonthlyReportRequest;
use App\Waste\Application\Alert\MonthlyReport\MonthlyReportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'waste:alerts:monthly-report',
    description: 'Send monthly alert report by email to account owners',
)]
class MonthlyReportCommand extends Command
{
    public function __construct(
        private readonly MonthlyReportService $monthlyReportService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('month', null, InputOption::VALUE_OPTIONAL, 'Month to report (YYYY-MM format). Defaults to previous month.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Informe mensual de alertas de residuos');

        $monthOption = $input->getOption('month');

        if (is_string($monthOption) && '' !== $monthOption) {
            $month = \DateTimeImmutable::createFromFormat('Y-m', $monthOption);

            if (false === $month) {
                $io->error(sprintf('Formato de mes inválido: %s. Use YYYY-MM.', $monthOption));

                return Command::FAILURE;
            }
        } else {
            $month = new \DateTimeImmutable('first day of last month midnight');
        }

        $user = UserMother::fromUsernameAndRole('system', Role::Admin);

        $response = $this->monthlyReportService->__invoke(
            new MonthlyReportRequest(month: $month),
            $user,
        );

        if (0 === $response->totalAlerts) {
            $io->info(sprintf('No hay alertas para el mes %s', $month->format('Y-m')));

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Se han enviado %d alertas de %d cuentas',
            $response->totalAlerts,
            count($response->alertsByAccount),
        ));

        return Command::SUCCESS;
    }
}
