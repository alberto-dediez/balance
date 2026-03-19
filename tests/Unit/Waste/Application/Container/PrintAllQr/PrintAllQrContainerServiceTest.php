<?php

namespace App\Tests\Unit\Waste\Application\Container\PrintAllQr;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Domain\Model\Container\ContainerQrPrinted;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Infrastructure\QrCode\FakeQrPdfGenerator;
use App\Tests\Unit\Waste\Infrastructure\QrCode\FakeQrPdfMerger;
use App\Waste\Application\Container\PrintAllQr\PrintAllQrContainerRequest;
use App\Waste\Application\Container\PrintAllQr\PrintAllQrContainerService;
use App\Waste\Application\Container\PrintAllQr\PrintAllQrContainerResponse;

class PrintAllQrContainerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $containerRepository = ContainerRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $container = ContainerMother::fromAccount($accountId);
        $containerRepository->add($container);

        $container = ContainerMother::fromAccount($accountId);
        $containerRepository->add($container);

        $container = ContainerMother::fromAccount($accountId);
        $containerRepository->add($container);

        $sut = $this->createSUT($containerRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $request = new PrintAllQrContainerRequest(
            accountId: $accountId
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof PrintAllQrContainerResponse);
        $this->assertNotEmpty($result->path);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerQrPrinted, 'event not launched');
        $this->assertEquals($result->path, $lastEvent->path());
    }

    private function createSUT(ContainerRepository $containerRepository): PrintAllQrContainerService
    {
        return new PrintAllQrContainerService(
            repository: $containerRepository,
            pdfGenerator: new FakeQrPdfGenerator(),
            pdfMerger: new FakeQrPdfMerger(),
            authorizer: new ContainerAuthorizer()
        );
    }
}