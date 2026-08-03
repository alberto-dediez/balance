<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Balance;

use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class BalanceContext implements Context
{
    private readonly AcceptanceClient $client;

    public function __construct(KernelBrowser $client)
    {
        $this->client = AcceptanceClient::instance($client);
    }

    public function getProjectRepository(): ProjectRepository
    {
        return $this->client->getContainer()->get(ProjectRepository::class);
    }

    #[Given('project :name with id :id exists')]
    public function projectWithIdExists(string $name, string $id): void
    {
        $project = ProjectMother::fromNameAndId($name, $id);

        $this->getProjectRepository()->add($project);
    }

    #[Given('project :name with id :id and location :location exists')]
    public function projectWithIdAndLocationExists(string $name, string $id, string $location): void
    {
        $project = ProjectMother::fromNameAndIdAndLocation($name, $id, $location);

        $this->getProjectRepository()->add($project);
    }
}
