<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Twig;

use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DestinationExtension extends AbstractExtension
{
    private array $destinations = [];

    public function __construct(private readonly DestinationRepository $destinationRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('destination_name', $this->destinationName(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [

        ];
    }

    public function destinationName(?DestinationId $destinationId): ?Destination
    {
        if (null == $destinationId) {
            return null;
        }

        if (!isset($this->destinations[(string) $destinationId])) {
            $this->destinations[(string) $destinationId] = $this->destinationRepository->find($destinationId);
        }

        return $this->destinations[(string) $destinationId];
    }
}
