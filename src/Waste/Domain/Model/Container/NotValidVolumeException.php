<?php

namespace App\Waste\Domain\Model\Container;

class NotValidVolumeException extends \RuntimeException
{
    public function __construct(?string $volume)
    {
        parent::__construct(
            sprintf("%s no es un volumen válido", $volume), 
            400
        );
    }
}