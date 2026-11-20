<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder;

use App\Library\Application\Folder\FolderAuthorizer;

class FolderAuthorizerMother
{
    public static function create(): FolderAuthorizer
    {
        return new FolderAuthorizer();
    }
}
