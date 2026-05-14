<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

final class IncidenceEvidence
{
    public function __construct(
        private IncidenceEvidenceId $id,
        private Incidence $incidence,
        private string $path,
        private string $originalName,
        private string $mimeType,
        private \DateTimeImmutable $uploadedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): IncidenceEvidenceId
    {
        return $this->id;
    }

    public function incidence(): Incidence
    {
        return $this->incidence;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function uploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }
}
