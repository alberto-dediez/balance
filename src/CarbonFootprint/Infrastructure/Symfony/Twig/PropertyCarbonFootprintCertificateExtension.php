<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Twig;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PropertyCarbonFootprintCertificateExtension extends AbstractExtension implements ResetInterface
{
    /** @var array<string, PropertyCarbonFootprintCertificate|null> */
    private array $cache = [];

    public function __construct(
        private readonly PropertyCarbonFootprintCertificateRepository $certificateRepository,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('property_carbon_footprint_certificate', $this->propertyCarbonFootprintCertificate(...)),
        ];
    }

    public function propertyCarbonFootprintCertificate(string $propertyCarbonFootprintId): ?PropertyCarbonFootprintCertificate
    {
        if (!array_key_exists($propertyCarbonFootprintId, $this->cache)) {
            $this->cache[$propertyCarbonFootprintId] = $this->certificateRepository->findCurrentByPropertyCarbonFootprintId(new PropertyCarbonFootprintId($propertyCarbonFootprintId));
        }

        return $this->cache[$propertyCarbonFootprintId];
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
