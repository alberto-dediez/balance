<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\AlertConfiguration\Update;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepositoryMother;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Application\AlertConfiguration\AlertConfigurationItemDto;
use App\Waste\Application\AlertConfiguration\Update\UpdateAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Update\UpdateAlertConfigurationService;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use App\Waste\Domain\Model\AlertConfiguration\DuplicateAlertConfigurationItemException;
use PHPUnit\Framework\TestCase;

class UpdateAlertConfigurationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = AlertConfigurationRepositoryMother::inMemory();

        $config = AlertConfigurationMother::registeredAlertConfiguration();
        $repository->add($config);

        $sut = $this->createSUT($repository);

        $request = new UpdateAlertConfigurationRequest(
            alertConfigurationId: $config->id(),
            items: [
                new AlertConfigurationItemDto(type: 'NoMeasurements', enabled: false, parameters: ['noMeasurementsDays' => 14]),
                new AlertConfigurationItemDto(type: 'AbnormallyHigh', enabled: true, parameters: ['deviationMultiplier' => 3.0]),
                new AlertConfigurationItemDto(type: 'ThresholdExceeded', enabled: false, parameters: ['thresholdPercentage' => 200.0]),
            ],
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($config->id()->equals($result->alertConfigurationId));

        $persisted = $repository->find($config->id());
        $this->assertNotNull($persisted);
        $this->assertEmpty($persisted->enabledItemsForType(AlertType::NoMeasurements));
        $this->assertNotEmpty($persisted->enabledItemsForType(AlertType::AbnormallyHigh));
        $this->assertEmpty($persisted->enabledItemsForType(AlertType::ThresholdExceeded));

        $nmItem = $persisted->items()->getByType(AlertType::NoMeasurements);
        $this->assertNotNull($nmItem);
        $this->assertEquals(14, $nmItem->parameters()['noMeasurementsDays']);

        $ahItem = $persisted->items()->getByType(AlertType::AbnormallyHigh);
        $this->assertNotNull($ahItem);
        $this->assertEquals(3.0, $ahItem->parameters()['deviationMultiplier']);
    }

    public function testItRejectsDuplicateItems(): void
    {
        $repository = AlertConfigurationRepositoryMother::inMemory();

        $config = AlertConfigurationMother::registeredAlertConfiguration();
        $repository->add($config);

        $sut = $this->createSUT($repository);

        $request = new UpdateAlertConfigurationRequest(
            alertConfigurationId: $config->id(),
            items: [
                new AlertConfigurationItemDto(type: 'NoMeasurements', enabled: true, parameters: ['noMeasurementsDays' => 5]),
                new AlertConfigurationItemDto(type: 'NoMeasurements', enabled: false, parameters: ['noMeasurementsDays' => 7]),
            ],
        );

        $this->expectException(DuplicateAlertConfigurationItemException::class);

        $sut($request, UserMother::adminUser());
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(AlertConfigurationRepositoryMother::inMemory());

        $config = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertTrue($sut->isHandled(new UpdateAlertConfigurationRequest(
            alertConfigurationId: $config->id(),
        )));
    }

    private function createSUT(AlertConfigurationRepository $repository): UpdateAlertConfigurationService
    {
        return new UpdateAlertConfigurationService(
            alertConfigurationRepository: $repository,
            authorizer: new AlertConfigurationAuthorizer(),
        );
    }
}
