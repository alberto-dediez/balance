<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Twig;

use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ProtocolExtension extends AbstractExtension
{
    /** @var Protocol[] */
    private array $protocols = [];

    public function __construct(private readonly ProtocolRepository $protocolRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('protocol_name', $this->protocolsName(...)),
        ];
    }

    public function protocolsName(ProtocolId $protocolId): ?Protocol
    {
        if (!isset($this->protocols[(string) $protocolId])) {
            $protocol = $this->protocolRepository->find($protocolId);

            if ($protocol instanceof Protocol) {
                $this->protocols[(string) $protocolId] = $protocol;
            }
        }

        return $this->protocols[(string) $protocolId] ?? null;
    }
}
