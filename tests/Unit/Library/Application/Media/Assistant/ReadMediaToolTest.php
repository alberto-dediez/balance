<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\Assistant;

use App\Library\Application\Media\Assistant\ReadMediaTool;
use App\Library\Application\Media\Download\DownloadMediaService;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Tests\Unit\Library\Application\Media\MediaAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ReadMediaToolTest extends TestCase
{
    private InMemoryMediaRepository $repository;

    private InMemoryMediaStorage $storage;

    private ReadMediaTool $sut;

    protected function setUp(): void
    {
        $this->repository = new InMemoryMediaRepository();
        $this->storage = MediaStorageMother::inMemory();

        $this->sut = new ReadMediaTool(
            new DownloadMediaService($this->repository, $this->storage, MediaAuthorizerMother::create()),
            MediaAuthorizerMother::create(),
            new StubCurrentAccountIdProvider(),
        );
    }

    public function testReadsATextFile(): void
    {
        $media = $this->store('notas.md', 'text/markdown', '# Notas');

        $result = $this->sut->execute($this->arguments($media), UserMother::adminUser(), $this->context());

        $this->assertSame('# Notas', $result->data['contents']);
        $this->assertFalse($result->data['truncated']);
        $this->assertSame('notas.md', $result->data['name']);
        $this->assertSame([], $result->attachments);
        $this->assertSame(['mediaId' => (string) $media->id()], $result->references);
        $this->assertSame('library_media_show', $result->link?->route);
    }

    public function testAttachesAPdfAndAnImage(): void
    {
        $pdf = $this->store('manual.pdf', 'application/pdf', '%PDF-1.4');
        $image = $this->store('captura.png', 'image/png', 'png-bytes');

        $pdfResult = $this->sut->execute($this->arguments($pdf), UserMother::adminUser(), $this->context());
        $imageResult = $this->sut->execute($this->arguments($image), UserMother::adminUser(), $this->context());

        $this->assertTrue($pdfResult->data['attached']);
        $this->assertSame('%PDF-1.4', $pdfResult->attachments[0]->contents);
        $this->assertTrue($pdfResult->attachments[0]->isPdf());
        $this->assertTrue($imageResult->attachments[0]->isImage());
    }

    public function testAMediaOfAnotherAccountIsDenied(): void
    {
        $media = MediaMother::fromNameAndAccount('ajeno.pdf', AccountId::generate());
        $this->repository->add($media);
        $this->storage->store($media->id(), null, 'pdf', MediaStorageMother::localFile());
        $foreign = UserMother::fromUsernameAndRoleAndAccount('otro@dediez.software', Role::User, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $this->sut->execute($this->arguments($media), $foreign, $this->context());
    }

    public function testAMediaWithoutStoredContentsFails(): void
    {
        $media = MediaMother::uploadedMedia();
        $this->repository->add($media);

        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->execute($this->arguments($media), UserMother::adminUser(), $this->context());
    }

    public function testAnUnknownMediaFails(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->execute(ToolArgumentsFactory::create($this->sut->parameters(), ['mediaId' => (string) MediaId::generate()], $this->context()), UserMother::adminUser(), $this->context());
    }

    public function testAnUnreadableTypeFails(): void
    {
        $media = $this->store('hoja.xlsx', 'application/vnd.ms-excel', 'xls');

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('no es de un tipo legible');

        $this->sut->execute($this->arguments($media), UserMother::adminUser(), $this->context());
    }

    public function testAFileLargerThanTheLimitFailsWithoutReadingIt(): void
    {
        $media = $this->store('grande.pdf', 'application/pdf', '%PDF');

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('supera el tamaño máximo');

        $this->sut->execute($this->arguments($media), UserMother::adminUser(), new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'), 100));
    }

    public function testClosesTheStreamWhenItSucceedsAndWhenItFails(): void
    {
        $this->storage = new class extends InMemoryMediaStorage {
            /** @var list<resource> */
            public array $streams = [];

            public function read(string $path): mixed
            {
                $stream = parent::read($path);
                $this->streams[] = $stream;

                return $stream;
            }
        };
        $this->sut = new ReadMediaTool(
            new DownloadMediaService($this->repository, $this->storage, MediaAuthorizerMother::create()),
            MediaAuthorizerMother::create(),
            new StubCurrentAccountIdProvider(),
        );
        $readable = $this->store('notas.md', 'text/markdown', '# Notas');
        $unreadable = $this->store('hoja.xlsx', 'application/vnd.ms-excel', 'xls');

        $this->sut->execute($this->arguments($readable), UserMother::adminUser(), $this->context());

        try {
            $this->sut->execute($this->arguments($unreadable), UserMother::adminUser(), $this->context());
            $this->fail('The file must be rejected');
        } catch (InvalidToolArgumentsException) {
        }

        $this->assertCount(2, $this->storage->streams);

        foreach ($this->storage->streams as $stream) {
            $this->assertFalse(is_resource($stream));
        }
    }

    public function testIsAReadTool(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
    }

    private function store(string $name, string $mimeType, string $contents): Media
    {
        $media = MediaMother::from(MediaId::generate(), $name, $mimeType);
        $this->repository->add($media);
        $this->storage->write($media->path(), MediaStorageMother::localFile($contents));

        return $media;
    }

    private function arguments(Media $media): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), ['mediaId' => (string) $media->id()], $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
