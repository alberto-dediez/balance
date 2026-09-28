<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Console;

use App\Materiality\Application\Seed\SeedStandardMaterialityMastersRequest;
use App\Materiality\Application\Seed\SeedStandardMaterialityMastersService;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'materiality:seed',
    description: 'Register the GHG Protocol standard materiality topics and the initial materiality questions',
)]
class SeedStandardMaterialityMastersCommand extends Command
{
    public function __construct(
        private readonly SeedStandardMaterialityMastersService $seedStandardMaterialityMasters,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of the user that performs the seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findByUsername($input->getArgument('username'));

        if (null === $user) {
            $io->error(sprintf('User "%s" not found.', $input->getArgument('username')));

            return Command::FAILURE;
        }

        $response = $this->seedStandardMaterialityMasters->__invoke(new SeedStandardMaterialityMastersRequest(), $user);

        $io->table(['Counter', 'Value'], [
            ['Registered topics', count($response->registeredTopics)],
            ['Skipped topics', $response->skippedTopics],
            ['Registered questions', count($response->registeredQuestions)],
        ]);

        if ([] !== $response->registeredTopics) {
            $io->section('Registered topics');
            $io->listing(array_map(
                static fn(MaterialityTopic $topic): string => sprintf('%s · %s', (string) $topic->number(), $topic->name()),
                $response->registeredTopics,
            ));
        }

        if ([] !== $response->registeredQuestions) {
            $io->section('Registered questions');
            $io->listing(array_map(
                static fn(MaterialityQuestion $question): string => sprintf('%s · %s', $question->criterion()->value, $question->text()),
                $response->registeredQuestions,
            ));
        }

        return Command::SUCCESS;
    }
}
