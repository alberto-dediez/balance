<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Console\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\GeneratePdf\GeneratePdfPreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\GeneratePdf\GeneratePdfPreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'food-loss:prevention-plan:generate-pdf',
    description: 'Regenera el PDF de un plan de prevención',
)]
class GeneratePdfCommand extends Command
{
    public function __construct(
        private readonly PreventionPlanRepository $preventionPlanRepository,
        private readonly GeneratePdfPreventionPlanService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('preventionPlanId', InputArgument::REQUIRED, 'UUID del plan de prevención')
            ->addOption('version-number', null, InputOption::VALUE_OPTIONAL, 'Número de versión (por defecto la última versión completada)')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale del PDF (es, en, fr, de, it, tr)', Locale::Spanish->value);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Regenerar PDF de plan de prevención');

        $preventionPlanId = new PreventionPlanId($input->getArgument('preventionPlanId'));
        $preventionPlan = $this->preventionPlanRepository->find($preventionPlanId);

        if (!$preventionPlan instanceof PreventionPlan) {
            $io->error(sprintf('No se encontró el plan de prevención con ID "%s"', $preventionPlanId));

            return Command::FAILURE;
        }

        $versionNumberOption = $input->getOption('version-number');
        if (null !== $versionNumberOption) {
            $versionNumber = (int) $versionNumberOption;
        } else {
            $lastVersion = $preventionPlan->versions()->lastCompletedVersion();
            if (null === $lastVersion) {
                $io->error('El plan de prevención no tiene versiones completadas');

                return Command::FAILURE;
            }
            $versionNumber = $lastVersion->versionNumber();
        }

        $locale = Locale::tryFrom((string) $input->getOption('locale'));
        if (null === $locale) {
            $io->error(sprintf('Locale inválido. Valores permitidos: %s', implode(', ', array_column(Locale::cases(), 'value'))));

            return Command::FAILURE;
        }

        $request = new GeneratePdfPreventionPlanRequest(
            preventionPlanId: $preventionPlanId,
            versionNumber: $versionNumber,
            locale: $locale,
        );

        ($this->service)($request, UserMother::systemUser());

        $io->success(sprintf('PDF generado correctamente para la versión %d en locale "%s"', $versionNumber, $locale->value));

        return Command::SUCCESS;
    }
}
