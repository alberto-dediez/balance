<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Packaging\Dashboard;

use App\FoodLoss\Application\Packaging\Dashboard\GetDoggyBagDashboardRequest;
use App\FoodLoss\Application\Packaging\Dashboard\GetDoggyBagDashboardService;
use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetDoggyBagDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';
    private const PROPERTY_ID = '00000000-0000-0000-0000-000000000010';
    private const OTHER_PROPERTY_ID = '00000000-0000-0000-0000-000000000020';
    private const CRITICAL_POINT_ID = '00000000-0000-0000-0000-000000000001';

    public function testUseCase(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $packagingRepository = PackagingRepositoryMother::inMemory();

        $reusable = PackagingMother::from(name: 'Tarrina', id: (string) PackagingId::generate());
        $reusable->setPropertyStock(new PropertyId(self::PROPERTY_ID), 10);
        $reusable->setPropertyStock(new PropertyId(self::OTHER_PROPERTY_ID), 5);
        $packagingRepository->add($reusable);

        $compostable = PackagingMother::from(name: 'Caja', id: (string) PackagingId::generate(), packagingType: PackagingType::Compostable);
        $compostable->setPropertyStock(new PropertyId(self::PROPERTY_ID), 3);
        $packagingRepository->add($compostable);

        $packagingRepository->add(PackagingMother::from(name: 'Ajena', id: (string) PackagingId::generate(), accountId: 'another-account-id'));

        $doggyBagSignRepository = DoggyBagSignRepositoryMother::inMemory();
        $doggyBagSignRepository->add(DoggyBagSignMother::from(name: 'Cartel 1', id: (string) DoggyBagSignId::generate(), accountId: self::ACCOUNT_ID, propertyId: new PropertyId(self::PROPERTY_ID)));
        $doggyBagSignRepository->add(DoggyBagSignMother::from(name: 'Cartel 2', id: (string) DoggyBagSignId::generate(), accountId: self::ACCOUNT_ID, propertyId: new PropertyId(self::PROPERTY_ID)));
        $doggyBagSignRepository->add(DoggyBagSignMother::random());

        $doggyBagExceptionRepository = DoggyBagExceptionRepositoryMother::inMemory();
        $doggyBagExceptionRepository->add(DoggyBagExceptionMother::from(description: 'Sopa', criticalPointId: self::CRITICAL_POINT_ID, accountId: self::ACCOUNT_ID, id: (string) DoggyBagExceptionId::generate()));
        $doggyBagExceptionRepository->add(DoggyBagExceptionMother::from(description: 'Crema', criticalPointId: self::CRITICAL_POINT_ID, accountId: self::ACCOUNT_ID, id: (string) DoggyBagExceptionId::generate()));
        $doggyBagExceptionRepository->add(DoggyBagExceptionMother::from(description: 'Ajena', criticalPointId: self::CRITICAL_POINT_ID, accountId: 'another-account-id', id: (string) DoggyBagExceptionId::generate()));

        $sut = $this->createSUT($packagingRepository, $doggyBagSignRepository, $doggyBagExceptionRepository);

        $request = new GetDoggyBagDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(2, $response->totalPackagings);
        $this->assertSame(18, $response->totalStock);
        $this->assertSame(2, $response->totalDoggyBagSigns);
        $this->assertSame(2, $response->totalDoggyBagExceptions);

        $stockByType = array_combine($response->stockByType->labels, $response->stockByType->values);
        $this->assertSame(15, $stockByType['Reusable']);
        $this->assertSame(3, $stockByType['Compostable']);
        $this->assertSame(0, $stockByType['Recyclable']);

        $this->assertSame(['Hotel Test', 'Hotel Two'], $response->stockByProperty->labels);
        $this->assertSame([13, 5], $response->stockByProperty->values);

        $this->assertSame(['Hotel Test'], $response->signsByProperty->labels);
        $this->assertSame([2], $response->signsByProperty->values);

        $this->assertSame(['Cocina'], $response->exceptionsByCriticalPoint->labels);
        $this->assertSame([2], $response->exceptionsByCriticalPoint->values);

        $this->assertSame([(new \DateTimeImmutable('NOW'))->format('m/Y')], $response->exceptionsByMonth->labels);
        $this->assertSame([2], $response->exceptionsByMonth->values);
    }

    public function testFiltersStockByProperty(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $packagingRepository = PackagingRepositoryMother::inMemory();

        $reusable = PackagingMother::from(name: 'Tarrina', id: (string) PackagingId::generate());
        $reusable->setPropertyStock(new PropertyId(self::PROPERTY_ID), 10);
        $reusable->setPropertyStock(new PropertyId(self::OTHER_PROPERTY_ID), 5);
        $packagingRepository->add($reusable);

        $sut = $this->createSUT($packagingRepository, DoggyBagSignRepositoryMother::inMemory(), DoggyBagExceptionRepositoryMother::inMemory());

        $request = new GetDoggyBagDashboardRequest(accountId: $accountId, propertyId: new PropertyId(self::OTHER_PROPERTY_ID));
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $response = $sut($request, $user);

        $this->assertSame(5, $response->totalStock);
        $this->assertSame(['Hotel Two'], $response->stockByProperty->labels);
        $this->assertSame([5], $response->stockByProperty->values);
    }

    public function testUnauthorizedUser(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);

        $sut = $this->createSUT(PackagingRepositoryMother::inMemory(), DoggyBagSignRepositoryMother::inMemory(), DoggyBagExceptionRepositoryMother::inMemory());

        $request = new GetDoggyBagDashboardRequest(accountId: $accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, new AccountId('another-account-id'));

        $this->expectException(UnauthorizedActionException::class);

        $sut($request, $user);
    }

    private function createSUT(
        PackagingRepository $packagingRepository,
        DoggyBagSignRepository $doggyBagSignRepository,
        DoggyBagExceptionRepository $doggyBagExceptionRepository,
    ): GetDoggyBagDashboardService {
        $criticalPointRepository = CriticalPointRepositoryMother::inMemory();
        $criticalPointRepository->add(CriticalPointMother::fromNameAndId('Cocina', self::CRITICAL_POINT_ID));

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Test', self::PROPERTY_ID));
        $propertyRepository->add(PropertyMother::fromNameAndId('Hotel Two', self::OTHER_PROPERTY_ID));

        return new GetDoggyBagDashboardService(
            repository: $packagingRepository,
            authorizer: new PackagingAuthorizer(),
            doggyBagSignRepository: $doggyBagSignRepository,
            doggyBagExceptionRepository: $doggyBagExceptionRepository,
            criticalPointRepository: $criticalPointRepository,
            propertyRepository: $propertyRepository,
        );
    }
}
