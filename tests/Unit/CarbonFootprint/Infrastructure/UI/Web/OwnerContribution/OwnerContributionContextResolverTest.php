<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution\OwnerContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution\OwnerContributionContextResolver;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class OwnerContributionContextResolverTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testResolvesTheAccountContextWithCompensation(): void
    {
        $account = AccountMother::withFeatures('MyAccount', (string) AccountId::generate(), new AccountFeatures());

        $context = $this->createSUT($account)(
            ProjectContributionOrigin::Account->value,
            (string) $account->id(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id()),
        );

        $this->assertInstanceOf(OwnerContributionContext::class, $context);
        $this->assertSame('MyAccount', $context->originName);
    }

    public function testDoesNotResolveAnyContextWithoutCompensation(): void
    {
        $account = AccountMother::withFeatures('MyAccount', (string) AccountId::generate(), new AccountFeatures(compensation: false));

        $context = $this->createSUT($account)(
            ProjectContributionOrigin::Account->value,
            (string) $account->id(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id()),
        );

        $this->assertNull($context);
    }

    private function createSUT(Account $account): OwnerContributionContextResolver
    {
        return new OwnerContributionContextResolver(
            new InMemoryEventRepository(),
            new InMemoryEventCarbonFootprintRepository(),
            new InMemoryPropertyRepository(),
            new InMemoryAccountRepository([(string) $account->id() => $account]),
            AccountFeaturesPolicyMother::create([$account]),
        );
    }
}
