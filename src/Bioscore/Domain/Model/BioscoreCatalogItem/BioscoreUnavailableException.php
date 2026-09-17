<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

final class BioscoreUnavailableException extends \RuntimeException
{
    public static function fromCatalog(BioscoreCatalog $catalog, ?\Throwable $previous = null): self
    {
        return new self(sprintf('The Bioscore catalog "%s" could not be downloaded.', $catalog->value), 0, $previous);
    }

    public static function forConsumptions(?\Throwable $previous = null): self
    {
        return new self('The Bioscore consumptions could not be downloaded.', 0, $previous);
    }

    public static function authenticationFailed(?\Throwable $previous = null): self
    {
        return new self('Could not authenticate against the Bioscore API.', 0, $previous);
    }
}
