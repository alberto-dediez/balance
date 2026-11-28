<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestFilter;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ContestRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
        parent::tearDown();
    }

    public function testAddFindAndRemove(): void
    {
        $repository = $this->createSUT();
        $contest = ContestMother::draft(
            hosts: [ContestMother::eventHost(), ContestMother::propertyHost()],
            allowedCountries: ['ES', 'PT'],
        );

        $this->assertNull($repository->find($contest->id()));

        $repository->add($contest);
        $this->clearPersistence();

        $found = $repository->find($contest->id());

        $this->assertInstanceOf(Contest::class, $found);
        $this->assertCount(2, $found->hostReferences());
        $this->assertEqualsCanonicalizing(['ES', 'PT'], $found->allowedCountryIsos());

        $repository->remove($found);

        $this->assertNull($repository->find($contest->id()));
    }

    public function testSynchronizesHostsOnUpdate(): void
    {
        $repository = $this->createSUT();
        $contest = ContestMother::draft(hosts: [ContestMother::eventHost(), ContestMother::propertyHost()]);
        $repository->add($contest);

        $contest->update(
            participationMode: $contest->participationMode(),
            awardMode: $contest->awardMode(),
            winnersCount: $contest->winnersCount(),
            platformDraw: $contest->platformDraw(),
            adultsOnly: $contest->adultsOnly(),
            name: $contest->name(),
            description: $contest->description(),
            prizeDescription: $contest->prizeDescription(),
            terms: $contest->terms(),
            organizerName: $contest->organizerName(),
            organizerTaxId: $contest->organizerTaxId(),
            organizerContactEmail: $contest->organizerContactEmail(),
            privacyPolicyUrl: null,
            startsAt: $contest->startsAt(),
            endsAt: $contest->endsAt(),
            hosts: [ContestMother::propertyHost(), ContestMother::tripLinkHost()],
            allowedCountries: [],
        );
        $repository->add($contest);
        $this->clearPersistence();

        $found = $repository->find($contest->id());

        $this->assertInstanceOf(Contest::class, $found);
        $this->assertFalse($found->hasHost(ContestMother::eventHost()));
        $this->assertTrue($found->hasHost(ContestMother::propertyHost()));
        $this->assertTrue($found->hasHost(ContestMother::tripLinkHost()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();
        $accountId = (string) AccountId::generate();

        $repository->add(ContestMother::draft(accountId: $accountId, name: 'Bravo'));
        $repository->add(ContestMother::published(accountId: $accountId, name: 'Alfa', awardMode: ContestAwardMode::FirstEntrants, winnersCount: 2, hosts: [ContestMother::propertyHost()]));
        $repository->add(ContestMother::draft(name: 'Otra cuenta'));

        $filter = new ContestFilter(accountId: new AccountId($accountId));

        $this->assertSame(['Alfa', 'Bravo'], array_map(static fn(Contest $contest): string => $contest->name(), $repository->findByFilter($filter)));
        $this->assertSame(2, $repository->countByFilter($filter));
        $this->assertSame(1, $repository->countByFilter(new ContestFilter(accountId: new AccountId($accountId), status: ContestStatus::Published)));
        $this->assertSame(1, $repository->countByFilter(new ContestFilter(accountId: new AccountId($accountId), awardMode: ContestAwardMode::FirstEntrants)));
        $this->assertSame(1, $repository->countByFilter(new ContestFilter(accountId: new AccountId($accountId), text: 'brav')));
    }

    public function testFindPublishedByHost(): void
    {
        $repository = $this->createSUT();
        $published = ContestMother::published(hosts: [ContestMother::eventHost('event-a'), ContestMother::propertyHost('property-a')]);
        $repository->add($published);
        $repository->add(ContestMother::draft(hosts: [ContestMother::eventHost('event-b')]));

        $this->assertTrue($repository->findPublishedByHost(ContestMother::propertyHost('property-a'))?->id()->equals($published->id()));
        $this->assertNull($repository->findPublishedByHost(ContestMother::eventHost('event-b')));
        $this->assertNull($repository->findPublishedByHost(ContestMother::tripLinkHost('event-a')));
    }

    public function testFindPublishedEndedBefore(): void
    {
        $repository = $this->createSUT();
        $ended = ContestMother::published(startsAt: new \DateTimeImmutable('-10 days'), endsAt: new \DateTimeImmutable('-1 day'), hosts: [ContestMother::eventHost('e1')]);
        $repository->add($ended);
        $repository->add(ContestMother::published(hosts: [ContestMother::eventHost('e2')]));

        $result = $repository->findPublishedEndedBefore(new \DateTimeImmutable('NOW'));

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->id()->equals($ended->id()));
    }

    public function testTranslationIsPersisted(): void
    {
        $repository = $this->createSUT();
        $contest = ContestMother::draft(name: 'Concurso');
        $contest->translate('Contest', 'Description', 'Prize', 'Terms', Locale::English);
        $repository->add($contest);
        $this->clearPersistence();

        $found = $repository->find($contest->id());

        $this->assertInstanceOf(Contest::class, $found);
        $this->assertSame('Concurso', $found->name(Locale::Spanish));
        $this->assertSame('Contest', $found->name(Locale::English));
    }

    protected function clearPersistence(): void {}

    abstract protected function createSUT(): ContestRepository;
}
