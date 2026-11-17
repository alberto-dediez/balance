<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Assistant;

use App\Library\Application\Media\Assistant\ListMediaTool;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ListMediaToolTest extends TestCase
{
    private InMemoryMediaRepository $repository;

    private AccountId $accountId;

    private ListMediaTool $sut;

    protected function setUp(): void
    {
        $this->repository = new InMemoryMediaRepository();
        $this->accountId = AccountId::generate();

        $this->sut = new ListMediaTool(
            new ListMediaService($this->repository, MediaAuthorizerMother::create()),
            MediaAuthorizerMother::create(),
            new StubCurrentAccountIdProvider($this->accountId),
        );
    }

    public function testListsTheMediaOfTheCurrentAccountOnly(): void
    {
        $own = MediaMother::fromNameAndAccount('contrato.pdf', $this->accountId);
        $own2 = MediaMother::fromName('logo.png');
        $this->repository->add($own);
        $this->repository->add(MediaMother::fromNameAndAccount('ajeno.pdf', AccountId::generate()));
        $this->repository->add($own2);

        $result = $this->sut->execute($this->arguments([]), $this->admin(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame([[
            'mediaId' => (string) $own->id(),
            'href' => 'route:library_media_show?mediaId=' . $own->id(),
            'name' => 'contrato.pdf',
            'fileName' => 'contrato.pdf',
            'mimeType' => 'image/png',
            'type' => 'Image',
            'size' => 2048,
            'registeredAt' => $result->data['media'][0]['registeredAt'],
            'readable' => true,
        ]], $result->data['media']);
    }

    public function testFiltersByNameAndMarksUnreadableFiles(): void
    {
        $this->repository->add(MediaMother::from(MediaId::generate(), 'hoja.xlsx', 'application/vnd.ms-excel', $this->accountId));
        $this->repository->add(MediaMother::fromNameAndAccount('foto.png', $this->accountId));

        $result = $this->sut->execute($this->arguments(['name' => 'hoja']), $this->admin(), $this->context());

        $this->assertCount(1, $result->data['media']);
        $this->assertFalse($result->data['media'][0]['readable']);
    }

    public function testWithoutCurrentAccountListsOnlyTheMediaWithoutAccount(): void
    {
        $platform = MediaMother::fromName('logo.png');
        $this->repository->add($platform);
        $this->repository->add(MediaMother::fromNameAndAccount('contrato.pdf', $this->accountId));
        $sut = new ListMediaTool(
            new ListMediaService($this->repository, MediaAuthorizerMother::create()),
            MediaAuthorizerMother::create(),
            new StubCurrentAccountIdProvider(),
        );

        $result = $sut->execute(ToolArgumentsFactory::create($sut->parameters(), [], $this->context()), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame((string) $platform->id(), $result->data['media'][0]['mediaId']);
    }

    public function testIsAReadTool(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo($this->admin()));
    }

    private function admin(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('admin@dediez.software', Role::Admin, $this->accountId);
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
