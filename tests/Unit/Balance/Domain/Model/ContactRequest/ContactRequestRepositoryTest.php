<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ContactRequest;

use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyExistsException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestFilter;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ContactRequestRepositoryTest extends KernelTestCase
{
    public function testAddContactRequestToRepository(): void
    {
        $repository = $this->createSUT();

        $contactRequest = ContactRequestMother::pending();

        $this->assertNull($repository->find($contactRequest->id()));

        $repository->add($contactRequest);

        $recovered = $repository->find($contactRequest->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($contactRequest->id()->equals($recovered->id()));
    }

    public function testFindOneByOrigin(): void
    {
        $repository = $this->createSUT();

        $contactRequest = ContactRequestMother::pending(origin: ProjectContributionOrigin::TripCalculation, originId: 'calc-1');

        $repository->add($contactRequest);
        $repository->add(ContactRequestMother::pending(origin: ProjectContributionOrigin::AttendeeSurveyResponse, originId: 'calc-2'));

        $found = $repository->findOneByOrigin(ProjectContributionOrigin::TripCalculation, 'calc-1');

        $this->assertNotNull($found);
        $this->assertTrue($contactRequest->id()->equals($found->id()));
        $this->assertNull($repository->findOneByOrigin(ProjectContributionOrigin::AttendeeSurveyResponse, 'calc-1'));
        $this->assertNull($repository->findOneByOrigin(ProjectContributionOrigin::TripCalculation, 'unknown'));
    }

    public function testAddingAnotherRequestWithTheSameOriginThrows(): void
    {
        $repository = $this->createSUT();

        $repository->add(ContactRequestMother::pending(origin: ProjectContributionOrigin::TripCalculation, originId: 'calc-1'));

        $this->expectException(ContactRequestAlreadyExistsException::class);

        $repository->add(ContactRequestMother::pending(origin: ProjectContributionOrigin::TripCalculation, originId: 'calc-1'));
    }

    public function testAddingTheSameRequestAgainDoesNotThrow(): void
    {
        $repository = $this->createSUT();

        $contactRequest = ContactRequestMother::pending(origin: ProjectContributionOrigin::TripCalculation, originId: 'calc-1');

        $repository->add($contactRequest);
        $repository->add($contactRequest);

        $this->assertSame(1, $repository->totalResultsByFilter(new ContactRequestFilter()));
    }

    public function testFindByFilterByResolved(): void
    {
        $repository = $this->createSUT();

        $repository->add(ContactRequestMother::pending());
        $repository->add(ContactRequestMother::pending());
        $repository->add(ContactRequestMother::managed());
        $repository->add(ContactRequestMother::discarded());

        $this->assertCount(2, $repository->findByFilter(new ContactRequestFilter(resolved: false, limit: 0)));
        $this->assertCount(2, $repository->findByFilter(new ContactRequestFilter(resolved: true, limit: 0)));
        $this->assertCount(4, $repository->findByFilter(new ContactRequestFilter(limit: 0)));
    }

    public function testFindByFilterByAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(ContactRequestMother::ofAccount($accountId));
        $repository->add(ContactRequestMother::ofAccount($accountId));
        $repository->add(ContactRequestMother::pending());

        $this->assertCount(2, $repository->findByFilter(new ContactRequestFilter(accountId: $accountId, limit: 0)));
    }

    public function testFindByFilterBySearch(): void
    {
        $repository = $this->createSUT();

        $repository->add(ContactRequestMother::pending(contactName: 'Lucía Martín', contactEmail: 'lucia@example.com'));
        $repository->add(ContactRequestMother::pending(contactName: 'Pedro Ruiz', contactEmail: 'pedro@acme.com'));

        $this->assertCount(1, $repository->findByFilter(new ContactRequestFilter(search: 'Lucía', limit: 0)));
        $this->assertCount(1, $repository->findByFilter(new ContactRequestFilter(search: 'acme', limit: 0)));
        $this->assertCount(0, $repository->findByFilter(new ContactRequestFilter(search: 'nadie', limit: 0)));
    }

    public function testFindByFilterOrdersByRequestedAtDescendingByDefault(): void
    {
        $repository = $this->createSUT();

        $oldest = ContactRequestMother::pending(requestedAt: new \DateTimeImmutable('2026-01-01 10:00:00'));
        $newest = ContactRequestMother::pending(requestedAt: new \DateTimeImmutable('2026-03-01 10:00:00'));
        $middle = ContactRequestMother::pending(requestedAt: new \DateTimeImmutable('2026-02-01 10:00:00'));

        $repository->add($oldest);
        $repository->add($newest);
        $repository->add($middle);

        $result = $repository->findByFilter(new ContactRequestFilter(limit: 0));

        $this->assertTrue($newest->id()->equals($result[0]->id()));
        $this->assertTrue($middle->id()->equals($result[1]->id()));
        $this->assertTrue($oldest->id()->equals($result[2]->id()));

        $filter = new ContactRequestFilter(limit: 0);
        $filter->orderBy = 'requestedAt';
        $filter->orderWay = 'ASC';

        $result = $repository->findByFilter($filter);

        $this->assertTrue($oldest->id()->equals($result[0]->id()));
        $this->assertTrue($newest->id()->equals($result[2]->id()));
    }

    public function testFindByFilterPaginatesAndCountsTotalResults(): void
    {
        $repository = $this->createSUT();

        $repository->add(ContactRequestMother::pending());
        $repository->add(ContactRequestMother::pending());
        $repository->add(ContactRequestMother::pending());

        $filter = new ContactRequestFilter(limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));

        $filter->page = 1;

        $this->assertCount(1, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): ContactRequestRepository;
}
