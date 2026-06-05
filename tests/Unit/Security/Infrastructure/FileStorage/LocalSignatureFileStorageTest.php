<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\FileStorage;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Infrastructure\FileStorage\LocalSignatureFileStorage;
use PHPUnit\Framework\TestCase;

class LocalSignatureFileStorageTest extends TestCase
{
    private string $uploadsDir;
    private LocalSignatureFileStorage $storage;

    protected function setUp(): void
    {
        $this->uploadsDir = sys_get_temp_dir() . '/signature_storage_test_' . uniqid();
        $this->storage = new LocalSignatureFileStorage($this->uploadsDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->uploadsDir);
    }

    public function testReturnsCorrectPath(): void
    {
        $id = SignatureRequestId::generate();

        $path = $this->storage->getSignedDocumentPath($id);

        $this->assertStringEndsWith('/signed.pdf', $path);
        $this->assertStringContainsString((string) $id, $path);
        $this->assertStringContainsString($this->uploadsDir . '/signatures/', $path);
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $id = SignatureRequestId::generate();

        $this->assertDirectoryDoesNotExist($this->uploadsDir);

        $path = $this->storage->getSignedDocumentPath($id);

        $this->assertDirectoryExists(dirname($path));
    }

    public function testIsIdempotent(): void
    {
        $id = SignatureRequestId::generate();

        $path1 = $this->storage->getSignedDocumentPath($id);
        $path2 = $this->storage->getSignedDocumentPath($id);

        $this->assertEquals($path1, $path2);
    }

    public function testDifferentIdsProduceDifferentPaths(): void
    {
        $id1 = SignatureRequestId::generate();
        $id2 = SignatureRequestId::generate();

        $path1 = $this->storage->getSignedDocumentPath($id1);
        $path2 = $this->storage->getSignedDocumentPath($id2);

        $this->assertNotEquals($path1, $path2);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
