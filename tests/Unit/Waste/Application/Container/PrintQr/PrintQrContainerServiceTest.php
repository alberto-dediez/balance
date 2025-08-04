<?php

namespace App\Tests\Unit\Waste\Application\Container\PrintQr;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerQrPrinted;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Application\Container\PrintQr\PrintQrContainerRequest;
use App\Waste\Application\Container\PrintQr\PrintQrContainerService;
use App\Waste\Application\Container\PrintQr\PrintQrContainerResponse;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Infrastructure\QrPdfGenerator\FakeQrPdfGenerator;

class PrintQrContainerServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $containerRepository = ContainerRepositoryMother::inMemory();

        $container = ContainerMother::random();

        $containerRepository->add($container);

        $sut = $this->createSUT($containerRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $container->accountId());

        $request = new PrintQrContainerRequest(
            containerId: $container->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof PrintQrContainerResponse);
        $this->assertNotEmpty((string) $result->path);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerQrPrinted, 'event not launched');
        $this->assertEquals($result->path, $lastEvent->path());
    }

    private function createSUT(ContainerRepository $containerRepository)
    {
        return new PrintQrContainerService(
            repository: $containerRepository,
            pdfGenerator: new FakeQrPdfGenerator(),
            authorizer: new ContainerAuthorizer()
        );
    }
}