<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Certificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateCodeGenerator;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;

final class RandomPropertyCarbonFootprintCertificateCodeGenerator implements PropertyCarbonFootprintCertificateCodeGenerator
{
    private const string ALPHABET = '23456789ABCDEFGHJKMNPQRSTWXYZ';

    private const int GROUPS = 3;

    private const int GROUP_LENGTH = 4;

    private const int MAX_ATTEMPTS = 10;

    public function __construct(private PropertyCarbonFootprintCertificateRepository $certificateRepository) {}

    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; ++$attempt) {
            $code = $this->draw();

            if (null === $this->certificateRepository->findByCode($code)) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate a unique property carbon footprint certificate code.');
    }

    private function draw(): string
    {
        $groups = [];
        $lastIndex = strlen(self::ALPHABET) - 1;

        for ($group = 0; $group < self::GROUPS; ++$group) {
            $characters = '';

            for ($position = 0; $position < self::GROUP_LENGTH; ++$position) {
                $characters .= self::ALPHABET[random_int(0, $lastIndex)];
            }

            $groups[] = $characters;
        }

        return implode('-', $groups);
    }
}
