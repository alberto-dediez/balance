<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Security\Domain\Model\Account\AccountId;

final class MediaPath
{
    public static function from(MediaId $mediaId, ?AccountId $accountId, string $extension): string
    {
        $scope = $accountId instanceof AccountId ? (string) $accountId : 'shared';

        return sprintf('%s/%s%s', $scope, (string) $mediaId, '' === $extension ? '' : '.' . $extension);
    }
}
