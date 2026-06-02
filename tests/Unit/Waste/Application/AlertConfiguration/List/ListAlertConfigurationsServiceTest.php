<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\AlertConfiguration\List;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepositoryMother;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Application\AlertConfiguration\List\ListAlertConfigurationsRequest;
use App\Waste\Application\AlertConfiguration\List\ListAlertConfigurationsService;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use PHPUnit\Framework\TestCase;

class ListAlertConfigurationsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testUseCase(): void
    {
        $repository = AlertConfigurationRepositoryMother::inMemory();
        $repository->add(AlertConfigurationMother::random());
        $repository->add(AlertConfigurationMother::random());

        $sut = $this->createSUT($repository);

        $result = $sut(new ListAlertConfigurationsRequest(limit: 0), UserMother::adminUser());

        $this->assertEquals(2, $result->totalResults);
        $this->assertCount(2, $result->alertConfigurations);
    }

    public function testEmptyRepository(): void
    {
        $repository = AlertConfigurationRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $result = $sut(new ListAlertConfigurationsRequest(), UserMother::adminUser());

        $this->assertEquals(0, $result->totalResults);
        $this->assertCount(0, $result->alertConfigurations);
    }

    private function createSUT(AlertConfigurationRepository $repository): ListAlertConfigurationsService
    {
        return new ListAlertConfigurationsService(
            repository: $repository,
            authorizer: new AlertConfigurationAuthorizer(),
        );
    }
}
