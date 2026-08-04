<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Balance;

use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
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

    public function getAccountProjectRepository(): AccountProjectRepository
    {
        return $this->client->getContainer()->get(AccountProjectRepository::class);
    }

    public function getCreditPurchaseRepository(): CreditPurchaseRepository
    {
        return $this->client->getContainer()->get(CreditPurchaseRepository::class);
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

    #[Given('project :id is adhered to account :accountId')]
    public function projectIsAdheredToAccount(string $id, string $accountId): void
    {
        $accountProject = new AccountProject(id: AccountProjectId::generate());
        $accountProject->select(new AccountId($accountId), new ProjectId($id));

        $this->getAccountProjectRepository()->add($accountProject);
    }

    #[Given('credit purchase :id for account :accountId and project :projectId requesting :tonnes tonnes exists')]
    public function creditPurchaseExists(string $id, string $accountId, string $projectId, string $tonnes): void
    {
        $creditPurchase = new CreditPurchase(id: new CreditPurchaseId($id));
        $creditPurchase->request(new AccountId($accountId), new ProjectId($projectId), UserId::generate(), (float) $tonnes);

        $this->getCreditPurchaseRepository()->add($creditPurchase);
    }

    #[Given('credit purchase :id is validated with :purchasedTonnes tonnes for :amount euros and reference :reference')]
    public function creditPurchaseIsValidated(string $id, string $purchasedTonnes, string $amount, string $reference): void
    {
        $creditPurchase = $this->getCreditPurchaseRepository()->find(new CreditPurchaseId($id));

        assert($creditPurchase instanceof CreditPurchase);

        $creditPurchase->validate((float) $purchasedTonnes, (float) $amount, $reference, null, UserId::generate());

        $this->getCreditPurchaseRepository()->add($creditPurchase);
    }
}
