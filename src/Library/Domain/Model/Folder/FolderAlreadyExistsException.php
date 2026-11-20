<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

class FolderAlreadyExistsException extends \RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Folder "%s" already exists', $name));
    }
}
