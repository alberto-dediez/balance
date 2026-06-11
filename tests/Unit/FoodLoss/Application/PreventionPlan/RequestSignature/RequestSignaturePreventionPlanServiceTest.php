<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\PreventionPlan\RequestSignature;

use App\FoodLoss\Application\PreventionPlan\RequestSignature\RequestSignaturePreventionPlanRequest;
use App\FoodLoss\Application\PreventionPlan\RequestSignature\RequestSignaturePreventionPlanService;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanSignatureRequested;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Application\SignatureRequest\Remove\RemoveSignatureRequestService;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemorySignatureRequestRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RequestSignaturePreventionPlanServiceTest extends TestCase
{
    private RequestSignaturePreventionPlanService $sut;
    private PreventionPlanRepository $preventionPlanRepository;
    private EmployeeRepository $employeeRepository;
    private InMemorySignatureRequestRepository $signatureRequestRepository;
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();
        $this->employeeRepository = EmployeeRepositoryMother::inMemory();
        $this->sut = $this->createSUT();
    }

    public function testIsHandle(): void
    {
        $plan = PreventionPlanMother::registeredPreventionPlan();

        $result = $this->sut->isHandled(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: $plan->id(),
            versionNumber: 1,
            pdfPath: '/tmp/plan.pdf',
        ));

        $this->assertTrue($result);
    }

    public function testRequestsSignatureForVersionWithResponsible(): void
    {
        $responsibleId = EmployeeId::generate();
        $plan = PreventionPlanMother::withResponsibleId($responsibleId);
        $this->preventionPlanRepository->add($plan);

        $employee = EmployeeMother::fromNameEmailAndId('John Doe', 'john@example.com', (string) $responsibleId);
        $this->employeeRepository->add($employee);

        touch('/tmp/plan.pdf');

        $user = UserMother::adminUser();

        $this->sut->__invoke(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: '/tmp/plan.pdf',
        ), $user);

        $updated = $this->preventionPlanRepository->find($plan->id());

        $this->assertNotNull($updated);
        $this->assertNotNull($updated->currentPlanVersion()->signatureRequestId());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(PreventionPlanSignatureRequested::class, $lastEvent);
    }

    public function testDoesNothingWhenPreventionPlanNotFound(): void
    {
        $user = UserMother::adminUser();

        $this->sut->__invoke(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: PreventionPlanId::generate(),
            versionNumber: 1,
            pdfPath: '/tmp/plan.pdf',
        ), $user);

        $this->assertNoSignatureRequestedEvent();
    }

    public function testDoesNothingWhenVersionNotFound(): void
    {
        $plan = PreventionPlanMother::registeredPreventionPlan();
        $this->preventionPlanRepository->add($plan);

        $user = UserMother::adminUser();

        $this->sut->__invoke(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: $plan->id(),
            versionNumber: 99,
            pdfPath: '/tmp/plan.pdf',
        ), $user);

        $this->assertNoSignatureRequestedEvent();
    }

    public function testDoesNothingWhenVersionHasNoResponsible(): void
    {
        $plan = PreventionPlanMother::withoutResponsible();
        $this->preventionPlanRepository->add($plan);

        $user = UserMother::adminUser();

        $this->sut->__invoke(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: '/tmp/plan.pdf',
        ), $user);

        $this->assertNoSignatureRequestedEvent();
    }

    public function testDoesNothingWhenResponsibleEmployeeNotFound(): void
    {
        $responsibleId = EmployeeId::generate();
        $plan = PreventionPlanMother::withResponsibleId($responsibleId);
        $this->preventionPlanRepository->add($plan);

        $user = UserMother::adminUser();

        $this->sut->__invoke(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: '/tmp/plan.pdf',
        ), $user);

        $this->assertNoSignatureRequestedEvent();
    }

    public function testDoesNothingWhenEmployeeHasNoEmail(): void
    {
        $responsibleId = EmployeeId::generate();
        $plan = PreventionPlanMother::withResponsibleId($responsibleId);
        $this->preventionPlanRepository->add($plan);

        $employee = EmployeeMother::fromNameEmailAndId('John Doe', '', (string) $responsibleId);
        $this->employeeRepository->add($employee);

        $user = UserMother::adminUser();

        $this->sut->__invoke(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: '/tmp/plan.pdf',
        ), $user);

        $this->assertNoSignatureRequestedEvent();
    }

    public function testRemovesPreviousSignatureRequestWhenAlreadyPresent(): void
    {
        $responsibleId = EmployeeId::generate();
        $plan = PreventionPlanMother::withResponsibleId($responsibleId);

        $existingSignatureRequestId = SignatureRequestId::generate();
        $plan->requestSignature($existingSignatureRequestId, $plan->currentVersion());
        $this->preventionPlanRepository->add($plan);

        $existingSignatureRequest = SignatureRequestMother::fromIdAndAccountId(
            (string) $existingSignatureRequestId,
            'account-id',
        );
        $this->signatureRequestRepository->add($existingSignatureRequest);

        $employee = EmployeeMother::fromNameEmailAndId('John Doe', 'john@example.com', (string) $responsibleId);
        $this->employeeRepository->add($employee);

        touch('/tmp/plan.pdf');

        $user = UserMother::adminUser();

        $this->sut->__invoke(new RequestSignaturePreventionPlanRequest(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: '/tmp/plan.pdf',
        ), $user);

        $this->assertNull($this->signatureRequestRepository->find($existingSignatureRequestId));
    }

    private function assertNoSignatureRequestedEvent(): void
    {
        $signatureEvents = array_filter(
            $this->spySubscriber->events(),
            static fn($e) => $e instanceof PreventionPlanSignatureRequested,
        );
        $this->assertCount(0, $signatureEvents);
    }

    private function createSUT(): RequestSignaturePreventionPlanService
    {
        $this->signatureRequestRepository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequestAuthorizer = new SignatureRequestAuthorizer();

        return new RequestSignaturePreventionPlanService(
            preventionPlanRepository: $this->preventionPlanRepository,
            employeeRepository: $this->employeeRepository,
            registerSignatureRequestService: new RegisterSignatureRequestService(
                signatureRequestRepository: $this->signatureRequestRepository,
                authorizer: $signatureRequestAuthorizer,
                appSecret: 'test-secret',
            ),
            removeSignatureRequestService: new RemoveSignatureRequestService(
                repository: $this->signatureRequestRepository,
                authorizer: $signatureRequestAuthorizer,
            ),
        );
    }
}
