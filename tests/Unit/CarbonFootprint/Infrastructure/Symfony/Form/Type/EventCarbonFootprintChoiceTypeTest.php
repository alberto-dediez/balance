<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EventCarbonFootprintChoiceType;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventCarbonFootprintChoiceTypeTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
    }

    public function testListsOnlyTheCompletedFootprintsOfTheCurrentAccount(): void
    {
        $own = $this->completedFootprint('account-id');
        $this->completedFootprint('other-account');
        $this->eventCarbonFootprintRepository->add(EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id'));

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

        $sut = new EventCarbonFootprintChoiceType($loggedAccount, $this->eventCarbonFootprintRepository, EventRepositoryMother::inMemory());

        $resolver = new OptionsResolver();
        $sut->configureOptions($resolver);

        /** @var array<string, string> $choices */
        $choices = $resolver->resolve()['choices'];

        return $choices;
    }

    private function completedFootprint(string $accountId): EventCarbonFootprint
    {
        $footprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), $accountId);
        $footprint->complete(new Emissions(), new Emissions());
        $this->eventCarbonFootprintRepository->add($footprint);

        return $footprint;
    }
}
