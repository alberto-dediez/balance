<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Data;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class EvidenceData
{
    public ?string $id = null;
    public ?string $originalName = null;
    public ?UploadedFile $file = null;
}
