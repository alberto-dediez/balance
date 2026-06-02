<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\AlertConfiguration\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepositoryMother;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Register\RegisterAlertConfigurationService;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRegistered;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use PHPUnit\Framework\TestCase;

class RegisterAlertConfigurationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = AlertConfigurationRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $request = new RegisterAlertConfigurationRequest(
            accountId: new AccountId('account-test'),
            items: [
                'NoMeasurements' => ['enabled' => true, 'parameters' => ['noMeasurementsDays' => 5]],
                'AbnormallyHigh' => ['enabled' => false, 'parameters' => ['deviationMultiplier' => 2.5]],
                'ThresholdExceeded' => ['enabled' => false, 'parameters' => ['thresholdPercentage' => 200.0]],
            ],
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->alertConfigurationId);

        $persisted = $repository->find($result->alertConfigurationId);
        $this->assertNotNull($persisted);

        $noMeasurementsItem = $persisted->items()->getByType(AlertType::NoMeasurements);
        $this->assertNotNull($noMeasurementsItem);
        $this->assertTrue($noMeasurementsItem->enabled());
        $this->assertEquals(5, $noMeasurementsItem->parameters()['noMeasurementsDays']);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(AlertConfigurationRegistered::class, $lastEvent);
    }

    public function testIsHandled(): void
    {
        $repository = AlertConfigurationRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new RegisterAlertConfigurationRequest(accountId: new AccountId('test'))));
    }

    private function createSUT(AlertConfigurationRepository $repository): RegisterAlertConfigurationService
    {
        return new RegisterAlertConfigurationService(
            alertConfigurationRepository: $repository,
            authorizer: new AlertConfigurationAuthorizer(),
        );
    }
}
