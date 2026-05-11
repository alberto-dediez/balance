<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Console\DonationAgreement;

use App\FoodLoss\Application\DonationAgreement\NotifyExpiring\NotifyExpiringDonationAgreementsService;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'food-loss:donation-agreement:notify-expiring',
    description: 'Notifica por email a los propietarios de cuentas cuyos acuerdos de donación están próximos a caducar',
)]
class NotifyExpiringCommand extends Command
{
    private const int DEFAULT_DAYS = 30;

    public function __construct(
        private readonly DonationAgreementRepository $donationAgreementRepository,
        private readonly NotifyExpiringDonationAgreementsService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Días de antelación para avisar de la caducidad',
            self::DEFAULT_DAYS,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Notificar acuerdos de donación próximos a caducar');

        $donationAgreements = $this->donationAgreementRepository->findExpiringSoon(
            (int) $input->getOption('days'),
        );

        if (0 === count($donationAgreements)) {
            $io->success('No hay acuerdos de donación próximos a caducar');

            return Command::SUCCESS;
        }

        foreach ($donationAgreements as $donationAgreement) {
            ($this->service)($donationAgreement);
        }

        $io->success(sprintf('Procesados %d acuerdo(s) próximos a caducar.', count($donationAgreements)));

        return Command::SUCCESS;
    }
}
