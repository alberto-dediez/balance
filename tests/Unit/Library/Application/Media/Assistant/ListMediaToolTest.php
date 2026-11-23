<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Assistant;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Media\Assistant\ListMediaTool;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ListMediaToolTest extends TestCase
{
    private InMemoryMediaRepository $repository;

    private InMemoryFolderRepository $folders;

    private AccountId $accountId;

    private ListMediaTool $sut;

    protected function setUp(): void
    {
        $this->repository = new InMemoryMediaRepository();
        $this->folders = FolderRepositoryMother::inMemory();
        $this->accountId = AccountId::generate();

        $this->sut = $this->createSUT($this->accountId);
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
            'folder' => null,
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
        $sut = $this->createSUT(null);

        $result = $sut->execute(ToolArgumentsFactory::create($sut->parameters(), [], $this->context()), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame((string) $platform->id(), $result->data['media'][0]['mediaId']);
    }

    public function testIsAReadTool(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo($this->admin()));
    }

    public function testListsTheMediaOfEveryFolderAndTellsTheFolderOfEachOne(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $this->folders->add($contracts);

        $this->repository->add(MediaMother::inFolder('firmado.pdf', $contracts->id(), $this->accountId));
        $this->repository->add(MediaMother::fromNameAndAccount('logo.png', $this->accountId));

        $result = $this->sut->execute($this->arguments([]), $this->admin(), $this->context());

        $this->assertSame(2, $result->data['total']);

        $folders = array_column($result->data['media'], 'folder', 'name');

        $this->assertSame('Contratos', $folders['firmado.pdf']);
        $this->assertNull($folders['logo.png']);
    }

    public function testTheFolderArgumentRestrictsToTheMatchingFolders(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $invoices = FolderMother::fromNameAndAccount('Facturas', $this->accountId);
        $this->folders->add($contracts);
        $this->folders->add($invoices);

        $this->repository->add(MediaMother::inFolder('firmado.pdf', $contracts->id(), $this->accountId));
        $this->repository->add(MediaMother::inFolder('factura.pdf', $invoices->id(), $this->accountId));
        $this->repository->add(MediaMother::fromNameAndAccount('logo.png', $this->accountId));

        $result = $this->sut->execute($this->arguments(['folder' => 'contr']), $this->admin(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame('firmado.pdf', $result->data['media'][0]['name']);
    }

    public function testAFolderArgumentWithoutMatchesListsNothing(): void
    {
        $this->repository->add(MediaMother::fromNameAndAccount('logo.png', $this->accountId));

        $result = $this->sut->execute($this->arguments(['folder' => 'nada']), $this->admin(), $this->context());

        $this->assertSame(0, $result->data['total']);
        $this->assertSame([], $result->data['media']);
        $this->assertSame('No hay ninguna carpeta con ese nombre', $result->summary);
    }

    public function testTheNameAlsoFindsTheMediaByTheNameOfItsFolder(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $this->folders->add($contracts);

        $this->repository->add(MediaMother::inFolder('firmado.pdf', $contracts->id(), $this->accountId));
        $this->repository->add(MediaMother::fromNameAndAccount('logo.png', $this->accountId));

        $result = $this->sut->execute($this->arguments(['name' => 'contr']), $this->admin(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame('firmado.pdf', $result->data['media'][0]['name']);
    }

    public function testTheFolderParameterIsDeclared(): void
    {
        $names = array_map(static fn($parameter): string => $parameter->name, $this->sut->parameters());

        $this->assertContains('folder', $names);
    }

    private function createSUT(?AccountId $accountId): ListMediaTool
    {
        $currentAccount = new StubCurrentAccountIdProvider($accountId);
        $folders = new CurrentAccountFolders($this->folders, $currentAccount);

        return new ListMediaTool(
            new ListMediaService($this->repository, MediaAuthorizerMother::create(), $folders),
            MediaAuthorizerMother::create(),
            $currentAccount,
            $folders,
        );
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
