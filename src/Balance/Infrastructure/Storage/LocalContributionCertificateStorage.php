<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Storage;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateStorage;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;

class LocalContributionCertificateStorage implements ContributionCertificateStorage
{
    public function __construct(private readonly string $basePath) {}

    public function store(ContributionCertificateId $id, ?AccountId $accountId, Locale $locale, string $localPath): string
    {
        $scope = $accountId instanceof AccountId ? (string) $accountId : 'shared';

        $relativePath = sprintf('%s/%s-%s.pdf', $scope, (string) $id, $locale->value);

        $absolutePath = $this->toAbsolutePath($relativePath);

        $directory = dirname($absolutePath);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create certificate directory "%s"', $directory));
        }

        if (false === copy($localPath, $absolutePath)) {
            throw new \RuntimeException(sprintf('Unable to store certificate file at "%s"', $absolutePath));
        }

        return $relativePath;
    }

    public function read(string $path): mixed
    {
        $stream = @fopen($this->toAbsolutePath($path), 'rb');

        if (false === $stream) {
            throw new \RuntimeException(sprintf('Unable to read certificate file "%s"', $path));
        }

        return $stream;
    }

    public function delete(?string $path): void
    {
        if (null === $path) {
            return;
        }

        $absolutePath = $this->toAbsolutePath($path);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function toAbsolutePath(string $relativePath): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($relativePath, '/');
    }
}
