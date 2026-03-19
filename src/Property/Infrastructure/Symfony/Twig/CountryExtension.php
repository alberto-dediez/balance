<?php

namespace App\Property\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(tags: [['name' => 'twig.extension', 'priority' => -10]])]
class CountryExtension extends AbstractExtension
{
    private array $countries = [];

    public function __construct(private readonly CountryRepository $countryRepository)
    {

    }

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

        if (count($this->countries) === 0) {
            $this->preload();
        }

        return $this->countries[(string) $countryId] ?? null;
    }

    private function preload(): void
    {
        $countries = $this->countryRepository->findByFilter(new CountryFilter(limit: 0));

        if (count($countries) === 0) {
            return ;
        }

        foreach ($countries as $country) {
            $this->countries[(string) $country->id()] = $country;
        }
    }
}
