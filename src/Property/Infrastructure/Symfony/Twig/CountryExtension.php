<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Twig;

use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

#[Autoconfigure(tags: [['name' => 'twig.extension', 'priority' => -10]])]
class CountryExtension extends AbstractExtension
{
    /** @var Country[] */
    private array $countries = [];

    public function __construct(private readonly CountryRepository $countryRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('country_name', $this->countryName(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [

        ];
    }

    public function countryName(?CountryId $countryId): ?Country
    {
        if (!$countryId instanceof CountryId) {
            return null;
        }

        if (0 === count($this->countries)) {
            $this->preload();
        }

        return $this->countries[(string) $countryId] ?? null;
    }

    private function preload(): void
    {
        $countries = $this->countryRepository->findByFilter(new CountryFilter(limit: 0));

        if (0 === count($countries)) {
            return;
        }

        foreach ($countries as $country) {
            $this->countries[(string) $country->id()] = $country;
        }
    }
}
