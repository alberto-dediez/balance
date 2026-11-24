<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media\ExtractBackup;

use App\Library\Application\Media\ExtractBackup\ExtractMediaBackupRequest;
use App\Library\Application\Media\ExtractBackup\ExtractMediaBackupService;
use App\Library\Domain\Model\Backup\MediaBackupNotFoundException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryMediaBackupRepository;
use App\Library\Infrastructure\Storage\InMemoryMediaStorage;
use App\Tests\Unit\Library\Domain\Model\Backup\MediaBackupMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaStorageMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ExtractMediaBackupServiceTest extends TestCase
{
    private InMemoryMediaBackupRepository $backups;

    private InMemoryMediaStorage $backup;

    private string $outputPath;

    protected function setUp(): void
    {
        $this->backups = new InMemoryMediaBackupRepository();
        $this->backup = MediaStorageMother::inMemoryWith(['shared/file.png' => 'contents of file.png']);
        $this->outputPath = sprintf('%s/media-backup-%s.png', sys_get_temp_dir(), uniqid());
    }

    protected function tearDown(): void
    {
        @unlink($this->outputPath);
    }

    public function testUseCase(): void
    {
        $this->backups->save(MediaBackupMother::forPath('shared/file.png', '2222'));

        $response = $this->createSUT()(new ExtractMediaBackupRequest('2222', $this->outputPath), UserMother::systemUser());

        $this->assertSame('shared/file.png', $response->path);
        $this->assertSame($this->outputPath, $response->outputPath);
        $this->assertSame('contents of file.png', file_get_contents($this->outputPath));
    }

    public function testExtractsTheBackupOfARemovedMedia(): void
    {
        $backup = MediaBackupMother::forPath('shared/file.png', '2222');
        $backup->markRemoved(new \DateTimeImmutable('2026-11-20 10:00:00'));
        $this->backups->save($backup);

        $this->createSUT()(new ExtractMediaBackupRequest('2222', $this->outputPath), UserMother::systemUser());

        $this->assertSame('contents of file.png', file_get_contents($this->outputPath));
    }

    public function testWithoutBackup(): void
    {
        $this->expectException(MediaBackupNotFoundException::class);

        $this->createSUT()(new ExtractMediaBackupRequest('2222', $this->outputPath), UserMother::systemUser());
    }

    public function testDoesNotOverwriteAnExistingFile(): void
    {
        $this->backups->save(MediaBackupMother::forPath('shared/file.png', '2222'));
        file_put_contents($this->outputPath, 'existing');

        try {
            $this->createSUT()(new ExtractMediaBackupRequest('2222', $this->outputPath), UserMother::systemUser());
            $this->fail('An existing file must not be overwritten');
        } catch (\InvalidArgumentException) {
            $this->assertSame('existing', file_get_contents($this->outputPath));
        }
    }

    public function testIsHandle(): void
    {
        $this->assertTrue($this->createSUT()->isHandled(new ExtractMediaBackupRequest('2222', $this->outputPath)));
    }

    private function createSUT(): ExtractMediaBackupService
    {
        return new ExtractMediaBackupService($this->backups, $this->backup);
    }
}
