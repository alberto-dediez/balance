<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\TrainingAttendance\RequestSignature;

use App\FoodLoss\Application\TrainingAttendance\RequestSignature\RequestTrainingAttendanceSignaturesRequest;
use App\FoodLoss\Application\TrainingAttendance\RequestSignature\RequestTrainingAttendanceSignaturesService;
use App\FoodLoss\Application\TrainingAttendance\TrainingAttendanceAuthorizer;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceSignatureRequested;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingSessionPdfGenerator;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\SignatureRequest\Register\RegisterSignatureRequestService;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemorySignatureRequestRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;

class RequestTrainingAttendanceSignaturesServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;
    private LocaleSwitcher $localeSwitcher;
    private string $tempPdfPath;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $this->localeSwitcher = $this->createMock(LocaleSwitcher::class);
        $this->localeSwitcher
            ->method('runWithLocale')
            ->willReturnCallback(static function (string $locale, \Closure $fn): mixed {
                return $fn();
            });

        $this->tempPdfPath = sys_get_temp_dir() . '/training-test-' . uniqid() . '.pdf';
        file_put_contents($this->tempPdfPath, 'fake pdf content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempPdfPath)) {
            unlink($this->tempPdfPath);
        }
    }

    public function testRequestsSignatureForEmployee(): void
    {
        $accountId = new AccountId('account-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $trainingSession = TrainingSessionMother::forAccount('account-test');
        $trainingSessionRepository->add($trainingSession);

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee = EmployeeMother::fromNameAndEmail('John Doe', 'john@example.com');
        $employeeRepository->add($employee);

        $attendanceRepository = TrainingAttendanceRepositoryMother::inMemory();

        $sut = $this->createSUT($attendanceRepository, $trainingSessionRepository, $employeeRepository);

        $request = new RequestTrainingAttendanceSignaturesRequest(
            trainingSessionId: $trainingSession->id(),
            employeeIds: [$employee->id()],
            accountId: $accountId,
        );

        $result = $sut($request, $user);

        $this->assertEquals(1, $result->requestedCount);

        $attendances = $attendanceRepository->findByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $trainingSession->id(), limit: 0),
        );

        $this->assertCount(1, $attendances);
        $this->assertTrue($attendances[0]->isInSignatureProcess());
        $this->assertNotNull($attendances[0]->signatureRequestId());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(TrainingAttendanceSignatureRequested::class, $lastEvent);
    }

    public function testRequestsSignatureForMultipleEmployees(): void
    {
        $accountId = new AccountId('account-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $trainingSession = TrainingSessionMother::forAccount('account-test');
        $trainingSessionRepository->add($trainingSession);

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee1 = EmployeeMother::fromNameAndEmail('Alice', 'alice@example.com');
        $employee2 = EmployeeMother::fromNameAndEmail('Bob', 'bob@example.com');
        $employeeRepository->add($employee1);
        $employeeRepository->add($employee2);

        $attendanceRepository = TrainingAttendanceRepositoryMother::inMemory();

        $sut = $this->createSUT($attendanceRepository, $trainingSessionRepository, $employeeRepository);

        $request = new RequestTrainingAttendanceSignaturesRequest(
            trainingSessionId: $trainingSession->id(),
            employeeIds: [$employee1->id(), $employee2->id()],
            accountId: $accountId,
        );

        $result = $sut($request, $user);

        $this->assertEquals(2, $result->requestedCount);

        $attendances = $attendanceRepository->findByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $trainingSession->id(), limit: 0),
        );
        $this->assertCount(2, $attendances);
    }

    public function testSkipsEmployeeAlreadyInSignatureProcess(): void
    {
        $accountId = new AccountId('account-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $trainingSession = TrainingSessionMother::forAccount('account-test');
        $trainingSessionRepository->add($trainingSession);

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee = EmployeeMother::fromNameAndEmail('John Doe', 'john@example.com');
        $employeeRepository->add($employee);

        $attendanceRepository = TrainingAttendanceRepositoryMother::inMemory();
        $existingAttendance = TrainingAttendanceMother::inSignatureProcess(
            accountId: 'account-test',
            trainingSessionId: $trainingSession->id(),
            employeeId: $employee->id(),
        );
        $attendanceRepository->add($existingAttendance);

        $sut = $this->createSUT($attendanceRepository, $trainingSessionRepository, $employeeRepository);

        $request = new RequestTrainingAttendanceSignaturesRequest(
            trainingSessionId: $trainingSession->id(),
            employeeIds: [$employee->id()],
            accountId: $accountId,
        );

        $result = $sut($request, $user);

        $this->assertEquals(1, $result->requestedCount);

        $attendances = $attendanceRepository->findByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $trainingSession->id(), limit: 0),
        );
        $this->assertCount(1, $attendances);
    }

    public function testSkipsEmployeeWithExistingFilePath(): void
    {
        $accountId = new AccountId('account-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $trainingSession = TrainingSessionMother::forAccount('account-test');
        $trainingSessionRepository->add($trainingSession);

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employee = EmployeeMother::fromNameAndEmail('John Doe', 'john@example.com');
        $employeeRepository->add($employee);

        $attendanceRepository = TrainingAttendanceRepositoryMother::inMemory();
        $existingAttendance = TrainingAttendanceMother::from(
            trainingSessionId: (string) $trainingSession->id(),
            employeeId: (string) $employee->id(),
            accountId: 'account-test',
            filePath: 'training_attendances/account-test/signed.pdf',
        );
        $attendanceRepository->add($existingAttendance);

        $sut = $this->createSUT($attendanceRepository, $trainingSessionRepository, $employeeRepository);

        $request = new RequestTrainingAttendanceSignaturesRequest(
            trainingSessionId: $trainingSession->id(),
            employeeIds: [$employee->id()],
            accountId: $accountId,
        );

        $result = $sut($request, $user);

        $this->assertEquals(1, $result->requestedCount);

        $attendances = $attendanceRepository->findByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $trainingSession->id(), limit: 0),
        );
        $this->assertCount(1, $attendances);
        $this->assertFalse($attendances[0]->isInSignatureProcess());
    }

    public function testSkipsEmployeeNotFoundInRepository(): void
    {
        $accountId = new AccountId('account-test');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $trainingSession = TrainingSessionMother::forAccount('account-test');
        $trainingSessionRepository->add($trainingSession);

        $attendanceRepository = TrainingAttendanceRepositoryMother::inMemory();

        $sut = $this->createSUT($attendanceRepository, $trainingSessionRepository, EmployeeRepositoryMother::inMemory());

        $request = new RequestTrainingAttendanceSignaturesRequest(
            trainingSessionId: $trainingSession->id(),
            employeeIds: [EmployeeId::generate()],
            accountId: $accountId,
        );

        $result = $sut($request, $user);

        $this->assertEquals(0, $result->requestedCount);

        $attendances = $attendanceRepository->findByFilter(new TrainingAttendanceFilter(limit: 0));
        $this->assertCount(0, $attendances);
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(
            TrainingAttendanceRepositoryMother::inMemory(),
            TrainingSessionRepositoryMother::inMemory(),
            EmployeeRepositoryMother::inMemory(),
        );

        $this->assertTrue($sut->isHandled(new RequestTrainingAttendanceSignaturesRequest()));
    }

    private function createSUT(
        TrainingAttendanceRepository $attendanceRepository,
        TrainingSessionRepository $trainingSessionRepository,
        EmployeeRepository $employeeRepository,
    ): RequestTrainingAttendanceSignaturesService {
        $tempPdfPath = $this->tempPdfPath;

        $pdfGenerator = new class ($tempPdfPath) implements TrainingSessionPdfGenerator {
            public function __construct(private readonly string $pdfPath) {}

            public function generate(TrainingSession $session, Employee $employee): string
            {
                return $this->pdfPath;
            }
        };

        $signatureRequestRepository = new InMemorySignatureRequestRepository();
        $registerSignatureRequestService = new RegisterSignatureRequestService(
            signatureRequestRepository: $signatureRequestRepository,
            authorizer: new SignatureRequestAuthorizer(),
            appSecret: 'test-secret',
        );

        $userRepository = new InMemoryUserRepository();

        return new RequestTrainingAttendanceSignaturesService(
            trainingAttendanceRepository: $attendanceRepository,
            trainingSessionRepository: $trainingSessionRepository,
            employeeRepository: $employeeRepository,
            authorizer: new TrainingAttendanceAuthorizer(),
            pdfGenerator: $pdfGenerator,
            registerSignatureRequestService: $registerSignatureRequestService,
            userRepository: $userRepository,
            localeSwitcher: $this->localeSwitcher,
        );
    }
}
