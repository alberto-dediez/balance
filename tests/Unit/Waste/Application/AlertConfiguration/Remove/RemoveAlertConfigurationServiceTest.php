<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\AlertConfiguration\Remove;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepositoryMother;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Application\AlertConfiguration\Remove\RemoveAlertConfigurationRequest;
use App\Waste\Application\AlertConfiguration\Remove\RemoveAlertConfigurationService;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use PHPUnit\Framework\TestCase;

class RemoveAlertConfigurationServiceTest extends TestCase
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

        $result = $sut(new RemoveAlertConfigurationRequest($config->id()), UserMother::adminUser());

        $this->assertTrue($config->id()->equals($result->alertConfigurationId));
        $this->assertNull($repository->find($config->id()));
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(AlertConfigurationRepositoryMother::inMemory());

        $config = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertTrue($sut->isHandled(new RemoveAlertConfigurationRequest(
            alertConfigurationId: $config->id(),
        )));
    }

    private function createSUT(AlertConfigurationRepository $repository): RemoveAlertConfigurationService
    {
        return new RemoveAlertConfigurationService(
            repository: $repository,
            authorizer: new AlertConfigurationAuthorizer(),
        );
    }
}
