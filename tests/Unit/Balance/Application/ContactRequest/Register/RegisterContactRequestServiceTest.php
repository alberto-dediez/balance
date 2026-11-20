<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContactRequest\Register;

use App\Balance\Application\ContactRequest\ContactRequestNotAvailableException;
use App\Balance\Application\ContactRequest\Register\RegisterContactRequestRequest;
use App\Balance\Application\ContactRequest\Register\RegisterContactRequestService;
use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyExistsException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestFilter;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRegistered;
use App\Balance\Domain\Model\ContactRequest\ContactRequestStatus;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountProjectRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContactRequestRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Application\CreditPurchase\Balance\CreditSourceSelectorMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ContactRequest\ContactRequestRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterContactRequestServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;
    private AccountId $accountId;
    private InMemoryContactRequestRepository $contactRequests;
    private InMemoryProjectRepository $projects;
    private InMemoryAccountProjectRepository $accountProjects;
    private InMemoryCreditPurchaseRepository $creditPurchases;
    private InMemoryProjectContributionRepository $contributions;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->accountId = AccountId::generate();
        $this->contactRequests = ContactRequestRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();
        $this->accountProjects = AccountProjectRepositoryMother::inMemory();
        $this->creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
    }

    public function testCreatesTheRequestWhenThereAreNoProjects(): void
    {
        $response = $this->createSUT()($this->request());

        $this->assertTrue($response->created);
        $this->assertSame(ContactRequestStatus::Pending, $response->contactRequest->status());
        $this->assertSame(1, $this->contactRequests->totalResultsByFilter(new ContactRequestFilter()));
        $this->assertInstanceOf(ContactRequestRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testCreatesTheRequestWhenTheAdheredProjectHasNotEnoughCredit(): void
    {
        $this->adheredProject(purchasedTonnes: 1.0);

        $response = $this->createSUT()($this->request(co2eqKg: 2000.0));

        $this->assertTrue($response->created);
        $this->assertSame(1, $this->contactRequests->totalResultsByFilter(new ContactRequestFilter()));
    }

    public function testThrowsWhenCompensationIsDisabled(): void
    {
        $this->expectException(ContactRequestNotAvailableException::class);

        $this->createSUT(new AccountFeatures(compensation: false))($this->request());
    }

    public function testThrowsWhenThereAreContributionOptions(): void
    {
        $this->adheredProject(purchasedTonnes: 10.0);

        $this->expectException(ContactRequestNotAvailableException::class);

        $this->createSUT()($this->request(co2eqKg: 2000.0));
    }

    public function testASecondSubmissionUpdatesThePendingRequest(): void
    {
        $sut = $this->createSUT();
        $first = $sut($this->request());
        $eventsBefore = count($this->spySubscriber->events());

        $response = $sut($this->request(
            contactName: 'Luis Pérez',
            contactEmail: 'luis@example.com',
            contactVatNumber: 'B12345678',
        ));

        $this->assertFalse($response->created);
        $this->assertTrue($first->contactRequest->id()->equals($response->contactRequest->id()));
        $this->assertSame(1, $this->contactRequests->totalResultsByFilter(new ContactRequestFilter()));
        $this->assertSame('Luis Pérez', $response->contactRequest->contactName());
        $this->assertSame('luis@example.com', $response->contactRequest->contactEmail());
        $this->assertSame('B12345678', $response->contactRequest->contactVatNumber());

        foreach (array_slice($this->spySubscriber->events(), $eventsBefore) as $event) {
            $this->assertNotInstanceOf(ContactRequestRegistered::class, $event);
        }
    }

    public function testASecondSubmissionLeavesAManagedRequestIntact(): void
    {
        $sut = $this->createSUT();
        $first = $sut($this->request());
        $first->contactRequest->markAsManaged(UserId::generate(), new \DateTimeImmutable('NOW'));
        $eventsBefore = count($this->spySubscriber->events());

        $response = $sut($this->request(contactEmail: 'otro@example.com'));

        $this->assertFalse($response->created);
        $this->assertSame('ana@example.com', $response->contactRequest->contactEmail());
        $this->assertSame(ContactRequestStatus::Managed, $response->contactRequest->status());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testASecondSubmissionLeavesADiscardedRequestIntact(): void
    {
        $sut = $this->createSUT();
        $first = $sut($this->request());
        $first->contactRequest->discard(UserId::generate(), new \DateTimeImmutable('NOW'));
        $eventsBefore = count($this->spySubscriber->events());

        $response = $sut($this->request(contactEmail: 'otro@example.com'));

        $this->assertFalse($response->created);
        $this->assertSame('ana@example.com', $response->contactRequest->contactEmail());
        $this->assertSame(ContactRequestStatus::Discarded, $response->contactRequest->status());
        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testTwoCalculationsOfTheSameAccountCreateTwoRequests(): void
    {
        $sut = $this->createSUT();

        $sut($this->request(originId: 'calc-1'));
        $sut($this->request(originId: 'calc-2'));

        $this->assertSame(2, $this->contactRequests->totalResultsByFilter(new ContactRequestFilter(accountId: $this->accountId)));
    }

    public function testASimultaneousSubmissionPropagatesTheExceptionWithoutDuplicatingTheRequest(): void
    {
        $this->contactRequests = new class extends InMemoryContactRequestRepository {
            public function findOneByOrigin(ProjectContributionOrigin $origin, string $originId): ?ContactRequest
            {
                return null;
            }
        };

        $sut = $this->createSUT();
        $sut($this->request());

        try {
            $sut($this->request());
            $this->fail('A simultaneous submission should throw ContactRequestAlreadyExistsException');
        } catch (ContactRequestAlreadyExistsException) {
        }

        $this->assertSame(1, $this->contactRequests->totalResultsByFilter(new ContactRequestFilter()));
    }

    private function request(
        string $originId = 'calc-1',
        float $co2eqKg = 150.0,
        string $contactName = 'Ana García',
        string $contactEmail = 'ana@example.com',
        ?string $contactVatNumber = null,
    ): RegisterContactRequestRequest {
        return new RegisterContactRequestRequest(
            contactRequestId: ContactRequestId::generate(),
            accountId: $this->accountId,
            propertyId: null,
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: $originId,
            originName: 'Congreso Anual',
            co2eqKg: $co2eqKg,
            contactName: $contactName,
            contactEmail: $contactEmail,
            contactPhone: null,
            contactCountry: 'ESP',
            contactIsCompany: null !== $contactVatNumber,
            contactCompanyName: null,
            contactVatNumber: $contactVatNumber,
            comment: null,
            locale: Locale::Spanish,
        );
    }

    private function adheredProject(float $purchasedTonnes): void
    {
        $projectId = ProjectId::generate();

        $this->projects->add(ProjectMother::contributable(id: $projectId));
        $this->accountProjects->add(AccountProjectMother::from($this->accountId, $projectId));
        $this->creditPurchases->add(CreditPurchaseMother::validated($this->accountId, $projectId, tonnes: $purchasedTonnes));
    }

    private function createSUT(AccountFeatures $features = new AccountFeatures()): RegisterContactRequestService
    {
        $accounts = [AccountMother::withFeatures('account', (string) $this->accountId, $features)];

        return new RegisterContactRequestService(
            $this->contactRequests,
            new ListContributionOptionsService(
                $this->accountProjects,
                $this->projects,
                new CreditAvailability($this->creditPurchases, $this->contributions),
                AccountFeaturesPolicyMother::create($accounts),
                CreditSourceSelectorMother::create(
                    $this->accountProjects,
                    $this->creditPurchases,
                    $this->contributions,
                    $accounts,
                    [],
                    [],
                ),
            ),
        );
    }
}
