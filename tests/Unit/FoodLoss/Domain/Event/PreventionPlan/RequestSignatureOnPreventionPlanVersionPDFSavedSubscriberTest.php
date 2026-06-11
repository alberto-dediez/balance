<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\RequestSignature\RequestSignaturePreventionPlanRequest;
use App\FoodLoss\Domain\Event\PreventionPlan\RequestSignatureOnPreventionPlanVersionPDFSavedSubscriber;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionDraftApproved;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionPDFSaved;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\LocalizedCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class RequestSignatureOnPreventionPlanVersionPDFSavedSubscriberTest extends TestCase
{
    private SluttyCommandHandler $spyCommandBus;
    private RequestSignatureOnPreventionPlanVersionPDFSavedSubscriber $sut;
    private PreventionPlanRepository $preventionPlanRepository;
    private EmployeeRepository $employeeRepository;
    private UserRepository $userRepository;
    private PreventionPlanVersionPDFSaved $spanishEvent;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();

        $this->preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();
        $this->employeeRepository = EmployeeRepositoryMother::inMemory();
        $this->userRepository = UserRepositoryMother::inMemory();

        $plan = PreventionPlanMother::registeredPreventionPlan();
        $this->preventionPlanRepository->add($plan);

        $this->spanishEvent = new PreventionPlanVersionPDFSaved(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: 'prevention-plans/plan-es.pdf',
            locale: Locale::Spanish,
        );

        $this->sut = $this->createSUT(loggedUserLocale: Locale::Spanish);
    }

    public function testIsSubscribedToPreventionPlanVersionPDFSaved(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->spanishEvent));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new PreventionPlanVersionDraftApproved(
            PreventionPlanId::generate(),
            1,
        )));
    }

    public function testDispatchesDeferredCommandWhenPdfLocaleMatchesLoggedUserFallbackLocale(): void
    {
        $this->sut->handle($this->spanishEvent);

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);

        $deferredCommand = $commands[0]['command'];
        $this->assertInstanceOf(DeferredCommand::class, $deferredCommand);

        $localizedCommand = $deferredCommand->command();
        $this->assertInstanceOf(LocalizedCommand::class, $localizedCommand);
        $this->assertEquals(Locale::Spanish, $localizedCommand->locale());

        $request = $localizedCommand->command();
        $this->assertInstanceOf(RequestSignaturePreventionPlanRequest::class, $request);
        $this->assertEquals($this->spanishEvent->preventionPlanId(), $request->preventionPlanId);
        $this->assertEquals($this->spanishEvent->versionNumber(), $request->versionNumber);
        $this->assertStringContainsString($this->spanishEvent->pdfPath(), $request->pdfPath);
    }

    public function testDoesNotDispatchWhenPdfLocaleDoesNotMatchLoggedUserLocale(): void
    {
        $englishEvent = new PreventionPlanVersionPDFSaved(
            preventionPlanId: $this->spanishEvent->preventionPlanId(),
            versionNumber: $this->spanishEvent->versionNumber(),
            pdfPath: 'prevention-plans/plan-en.pdf',
            locale: Locale::English,
        );

        $this->sut->handle($englishEvent);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testDoesNothingWhenPreventionPlanNotFound(): void
    {
        $unknownEvent = new PreventionPlanVersionPDFSaved(
            preventionPlanId: PreventionPlanId::generate(),
            versionNumber: 1,
            pdfPath: 'prevention-plans/unknown.pdf',
            locale: Locale::Spanish,
        );

        $this->sut->handle($unknownEvent);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testDispatchesDeferredCommandUsingResponsibleUserPreferredLocale(): void
    {
        $responsibleId = EmployeeId::generate();
        $responsibleUserId = UserId::generate();

        $employee = EmployeeMother::withUserId((string) $responsibleId, (string) $responsibleUserId);
        $this->employeeRepository->add($employee);

        $responsibleUser = new User(id: $responsibleUserId, preferredLocale: Locale::English);
        $this->userRepository->add($responsibleUser);

        $plan = PreventionPlanMother::withResponsibleId($responsibleId);
        $this->preventionPlanRepository->add($plan);

        $englishEvent = new PreventionPlanVersionPDFSaved(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: 'prevention-plans/plan-en.pdf',
            locale: Locale::English,
        );

        $this->sut->handle($englishEvent);

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);

        $localizedCommand = $commands[0]['command']->command();
        $this->assertInstanceOf(LocalizedCommand::class, $localizedCommand);
        $this->assertEquals(Locale::English, $localizedCommand->locale());
    }

    public function testDoesNotDispatchWhenResponsibleUserPrefersOtherLocale(): void
    {
        $responsibleId = EmployeeId::generate();
        $responsibleUserId = UserId::generate();

        $employee = EmployeeMother::withUserId((string) $responsibleId, (string) $responsibleUserId);
        $this->employeeRepository->add($employee);

        $responsibleUser = new User(id: $responsibleUserId, preferredLocale: Locale::English);
        $this->userRepository->add($responsibleUser);

        $plan = PreventionPlanMother::withResponsibleId($responsibleId);
        $this->preventionPlanRepository->add($plan);

        $spanishEventForOtherPlan = new PreventionPlanVersionPDFSaved(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
            pdfPath: 'prevention-plans/plan-es.pdf',
            locale: Locale::Spanish,
        );

        $this->sut->handle($spanishEventForOtherPlan);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    private function createSUT(Locale $loggedUserLocale = Locale::Spanish): RequestSignatureOnPreventionPlanVersionPDFSavedSubscriber
    {
        return new RequestSignatureOnPreventionPlanVersionPDFSavedSubscriber(
            uploadsDir: '/uploads',
            loggedUser: new FakeLoggedUser(new User(id: UserId::generate(), preferredLocale: $loggedUserLocale)),
            preventionPlanRepository: $this->preventionPlanRepository,
            employeeRepository: $this->employeeRepository,
            userRepository: $this->userRepository,
        );
    }
}
