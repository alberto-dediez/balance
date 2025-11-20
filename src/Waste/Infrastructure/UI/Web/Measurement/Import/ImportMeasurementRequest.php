<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Import;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportMeasurementRequest
{
    public ?UploadedFile $file = null;

    public int $offset = 0;
}
