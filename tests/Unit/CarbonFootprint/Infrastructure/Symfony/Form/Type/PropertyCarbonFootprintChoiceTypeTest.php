<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\PropertyCarbonFootprintChoiceType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyCarbonFootprintChoiceTypeTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyCarbonFootprintRepository = new InMemoryPropertyCarbonFootprintRepository();
    }

    public function testListsOnlyTheCompletedFootprintsOfTheCurrentAccount(): void
    {
        $own = $this->completedFootprint('account-id');
        $this->completedFootprint('other-account');
        $this->propertyCarbonFootprintRepository->add(PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id'));

        $choices = $this->choices(AccountMother::fromNameAndId('Acme', 'account-id'));

        self::assertSame([(string) $own->id()], array_values($choices));
    }

    public function testListsNoFootprintsWithoutCurrentAccount(): void
    {
        $this->completedFootprint('account-id');
        $this->completedFootprint('other-account');

        self::assertSame([], $this->choices(null));
    }

    /** @return array<string, string> */
    private function choices(?Account $account): array
    {
        $loggedAccount = $this->createStub(LoggedAccount::class);
        $loggedAccount->method('__invoke')->willReturn($account);

        $sut = new PropertyCarbonFootprintChoiceType($loggedAccount, $this->propertyCarbonFootprintRepository, PropertyRepositoryMother::inMemory());

        $resolver = new OptionsResolver();
        $sut->configureOptions($resolver);

        /** @var array<string, string> $choices */
        $choices = $resolver->resolve()['choices'];

        return $choices;
    }

    private function completedFootprint(string $accountId): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), $accountId);
        $footprint->complete(new Emissions());
        $this->propertyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }
}
