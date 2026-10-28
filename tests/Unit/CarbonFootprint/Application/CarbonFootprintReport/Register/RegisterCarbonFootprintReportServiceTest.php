<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Register;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\GenerateCarbonFootprintReportTextsRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Register\RegisterCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Register\RegisterCarbonFootprintReportService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class RegisterCarbonFootprintReportServiceTest extends TestCase
{
    private SluttyCommandHandler $spyCommandBus;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprints;
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprints;
    private InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprints;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();
        $this->eventCarbonFootprints = EventCarbonFootprintRepositoryMother::inMemory();
        $this->propertyCarbonFootprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->companyCarbonFootprints = CompanyCarbonFootprintRepositoryMother::inMemory();
    }

    /** @return iterable<string, array{0: CarbonFootprintReportSubjectType}> */
    public static function subjectTypes(): iterable
    {
        yield 'event' => [CarbonFootprintReportSubjectType::Event];
        yield 'property' => [CarbonFootprintReportSubjectType::Property];
        yield 'company' => [CarbonFootprintReportSubjectType::Company];
    }

    /** @dataProvider subjectTypes */
    public function testRegistersTheReportAndDispatchesTheTextsGeneration(CarbonFootprintReportSubjectType $subjectType): void
    {
        $carbonFootprintId = $this->givenCompletedFootprint($subjectType, 'account-id-test');
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();

        $response = ($this->createSUT($reportRepository))(new RegisterCarbonFootprintReportRequest($subjectType, $carbonFootprintId), $this->owner());

        $persisted = $reportRepository->find($response->reportId);
        $this->assertNotNull($persisted);
        $this->assertSame(CarbonFootprintReportStatus::GeneratingTexts, $persisted->status());
        $this->assertSame($subjectType, $persisted->subjectType());
        $this->assertSame($carbonFootprintId, $persisted->carbonFootprintId());
        $this->assertSame(Locale::Spanish, $persisted->baseLocale());

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(GenerateCarbonFootprintReportTextsRequest::class, $commands[0]['command']->command());
    }

    /** @dataProvider subjectTypes */
    public function testThrowsWhenTheFootprintIsNotCompleted(CarbonFootprintReportSubjectType $subjectType): void
    {
        $carbonFootprintId = $this->givenFootprint($subjectType, 'account-id-test');

        $this->expectException(\RuntimeException::class);

        ($this->createSUT(CarbonFootprintReportRepositoryMother::inMemory()))(new RegisterCarbonFootprintReportRequest($subjectType, $carbonFootprintId), $this->owner());
    }

    /** @dataProvider subjectTypes */
    public function testThrowsWhenAReportAlreadyExists(CarbonFootprintReportSubjectType $subjectType): void
    {
        $carbonFootprintId = $this->givenCompletedFootprint($subjectType, 'account-id-test');
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $reportRepository->add(CarbonFootprintReportMother::registeredFor($subjectType, $carbonFootprintId));

        $this->expectException(CarbonFootprintReportAlreadyExistsException::class);

        ($this->createSUT($reportRepository))(new RegisterCarbonFootprintReportRequest($subjectType, $carbonFootprintId), $this->owner());
    }

    /** @dataProvider subjectTypes */
    public function testOwnerOfAnotherAccountIsDenied(CarbonFootprintReportSubjectType $subjectType): void
    {
        $carbonFootprintId = $this->givenCompletedFootprint($subjectType, 'other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->createSUT(CarbonFootprintReportRepositoryMother::inMemory()))(new RegisterCarbonFootprintReportRequest($subjectType, $carbonFootprintId), $this->owner());
    }

    public function testThrowsWhenTheFootprintDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->createSUT(CarbonFootprintReportRepositoryMother::inMemory()))(new RegisterCarbonFootprintReportRequest(CarbonFootprintReportSubjectType::Property, (string) PropertyCarbonFootprintId::generate()), $this->owner());
    }

    private function givenFootprint(CarbonFootprintReportSubjectType $subjectType, string $accountId): string
    {
        switch ($subjectType) {
            case CarbonFootprintReportSubjectType::Event:
                $footprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), $accountId);
                $this->eventCarbonFootprints->add($footprint);

                return (string) $footprint->id();
            case CarbonFootprintReportSubjectType::Property:
                $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), $accountId);
                $this->propertyCarbonFootprints->add($footprint);

                return (string) $footprint->id();
            default:
                $footprint = CompanyCarbonFootprintMother::withProvidersAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), $accountId);
                $this->companyCarbonFootprints->add($footprint);

                return (string) $footprint->id();
        }
    }

    private function givenCompletedFootprint(CarbonFootprintReportSubjectType $subjectType, string $accountId): string
    {
        $id = $this->givenFootprint($subjectType, $accountId);

        switch ($subjectType) {
            case CarbonFootprintReportSubjectType::Event:
                $footprint = $this->eventCarbonFootprints->find(new EventCarbonFootprintId($id));
                $footprint?->complete(new Emissions(), new Emissions());
                break;
            case CarbonFootprintReportSubjectType::Property:
                $this->propertyCarbonFootprints->find(new PropertyCarbonFootprintId($id))?->complete(new Emissions());
                break;
            default:
                $this->companyCarbonFootprints->find(new CompanyCarbonFootprintId($id))?->complete(new Emissions());
        }

        return $id;
    }

    private function createSUT(CarbonFootprintReportRepository $reportRepository): RegisterCarbonFootprintReportService
    {
        $subjects = new CarbonFootprintReportSubjects(
            $this->eventCarbonFootprints,
            $this->propertyCarbonFootprints,
            $this->companyCarbonFootprints,
            new ReopenEventCarbonFootprintService($this->eventCarbonFootprints, new EventCarbonFootprintAuthorizer()),
            new ReopenPropertyCarbonFootprintService($this->propertyCarbonFootprints, new PropertyCarbonFootprintAuthorizer()),
            new ReopenCompanyCarbonFootprintService($this->companyCarbonFootprints, new CompanyCarbonFootprintAuthorizer()),
        );

        return new RegisterCarbonFootprintReportService(
            $subjects,
            new EventCarbonFootprintAuthorizer(),
            new PropertyCarbonFootprintAuthorizer(),
            new CompanyCarbonFootprintAuthorizer(),
            $reportRepository,
            'es|en',
        );
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
