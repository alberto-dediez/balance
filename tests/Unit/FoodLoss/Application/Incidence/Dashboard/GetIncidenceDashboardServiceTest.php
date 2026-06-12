<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Incidence\Dashboard;

use App\FoodLoss\Application\Incidence\Dashboard\GetIncidenceDashboardRequest;
use App\FoodLoss\Application\Incidence\Dashboard\GetIncidenceDashboardService;
use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Domain\Model\Incidence\IncidenceEvidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetIncidenceDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';
    private const PROPERTY_ID = '00000000-0000-0000-0000-000000000010';
    private const OTHER_PROPERTY_ID = '00000000-0000-0000-0000-000000000020';

    public function testUseCase(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $incidenceRepository = IncidenceRepositoryMother::inMemory();

        $incidenceWithEvidenceId = IncidenceId::generate();
        $withEvidence = IncidenceMother::from(
            name: 'Avería cámara',
            id: (string) $incidenceWithEvidenceId,
            propertyId: new PropertyId(self::PROPERTY_ID),
            exceptionOccurredOn: new \DateTimeImmutable('2026-01-10'),
        );
        $withEvidence->addEvidence(IncidenceEvidenceId::generate(), 'incidences/account-id-test/photo.jpg', 'photo.jpg', 'image/jpeg');
        $incidenceRepository->add($withEvidence);

        $incidenceHornoId = IncidenceId::generate();
        $incidenceRepository->add(IncidenceMother::from(
            name: 'Avería horno',
            id: (string) $incidenceHornoId,
            propertyId: new PropertyId(self::PROPERTY_ID),
            exceptionOccurredOn: new \DateTimeImmutable('2026-03-15'),
        ));
        $incidenceRepository->add(IncidenceMother::from(
            name: 'Comida en mal estado',
            id: (string) IncidenceId::generate(),
            propertyId: new PropertyId(self::OTHER_PROPERTY_ID),
            incidenceType: IncidenceType::SpoiledFood,
            exceptionOccurredOn: new \DateTimeImmutable('2026-03-20'),
        ));
        $incidenceRepository->add(IncidenceMother::from(
            name: 'Ajena',
            id: (string) IncidenceId::generate(),
            accountId: 'another-account-id',
        ));

        $surplusRepository = SurplusRepositoryMother::inMemory();
        $surplusRepository->add(SurplusMother::withIncidenceId((string) SurplusId::generate(), self::ACCOUNT_ID, $incidenceWithEvidenceId));
        $surplusRepository->add(SurplusMother::withIncidenceId((string) SurplusId::generate(), self::ACCOUNT_ID, $incidenceWithEvidenceId));
        $surplusRepository->add(SurplusMother::withIncidenceId((string) SurplusId::generate(), self::ACCOUNT_ID, $incidenceHornoId));

        $sut = $this->createSUT($incidenceRepository, $surplusRepository);

        $request = new GetIncidenceDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(3, $response->totalIncidences);
        $this->assertSame('machinery_failure', $response->mostFrequentIncidenceType);
        $this->assertSame(1, $response->withEvidencesIncidences);
        $this->assertSame(33.3, $response->withEvidencesPercentage());
        $this->assertSame(2, $response->affectedProperties);

        $typeCounts = array_combine($response->byType->labels, $response->byType->values);
        $this->assertSame(2, $typeCounts['machinery_failure']);
        $this->assertSame(0, $typeCounts['ngo_rejection']);
        $this->assertSame(1, $typeCounts['spoiled_food']);

        $this->assertSame(['Hotel Test', 'Hotel Two'], $response->byProperty->labels);
        $this->assertSame([2, 1], $response->byProperty->values);

        $this->assertSame(['01/2026', '02/2026', '03/2026'], $response->incidencesByMonth->labels);
        $this->assertSame([1, 0, 2], $response->incidencesByMonth->values);

        $surplusCounts = array_combine($response->surplusByIncidence->labels, $response->surplusByIncidence->values);
        $this->assertSame(2, $surplusCounts['Avería cámara']);
        $this->assertSame(1, $surplusCounts['Avería horno']);
        $this->assertArrayNotHasKey('Comida en mal estado', $surplusCounts);
    }

    public function testFiltersByDateRange(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $incidenceRepository = IncidenceRepositoryMother::inMemory();
        $incidenceRepository->add(IncidenceMother::from(
            name: 'Avería cámara',
            id: (string) IncidenceId::generate(),
            exceptionOccurredOn: new \DateTimeImmutable('2026-01-10'),
        ));
        $incidenceRepository->add(IncidenceMother::from(
            name: 'Comida en mal estado',
            id: (string) IncidenceId::generate(),
            incidenceType: IncidenceType::SpoiledFood,
            exceptionOccurredOn: new \DateTimeImmutable('2026-03-20'),
        ));

        $sut = $this->createSUT($incidenceRepository);

        $request = new GetIncidenceDashboardRequest(accountId: $accountId);
        $request->dateFrom = new \DateTimeImmutable('2026-02-01');
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(1, $response->totalIncidences);
        $this->assertSame('spoiled_food', $response->mostFrequentIncidenceType);
        $this->assertSame(['03/2026'], $response->incidencesByMonth->labels);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT(IncidenceRepositoryMother::inMemory());

        $request = new GetIncidenceDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $accountId);

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function createSUT(IncidenceRepository $incidenceRepository, ?SurplusRepository $surplusRepository = null): GetIncidenceDashboardService
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Test', self::PROPERTY_ID));
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Two', self::OTHER_PROPERTY_ID));

        return new GetIncidenceDashboardService(
            repository: $incidenceRepository,
            authorizer: new IncidenceAuthorizer(),
            propertyRepository: $propertyRepository,
            surplusRepository: $surplusRepository ?? SurplusRepositoryMother::inMemory(),
        );
    }
}
