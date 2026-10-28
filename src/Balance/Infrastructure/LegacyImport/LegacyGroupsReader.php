<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final class LegacyGroupsReader
{
    private const string SEPARATOR = ';';

    private const string ORGANIZATION_ID = 'organization_id';

    public function read(string $path, LegacyBalanceDataset $dataset): LegacyGroups
    {
        $file = basename($path);

        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('El fichero "%s" no existe.', $file));
        }

        if (!is_readable($path)) {
            throw new \RuntimeException(sprintf('El fichero "%s" no se puede leer.', $file));
        }

        $handle = fopen($path, 'r');

        if (false === $handle) {
            throw new \RuntimeException(sprintf('El fichero "%s" no se puede leer.', $file));
        }

        try {
            return $this->groups($handle, $dataset);
        } finally {
            fclose($handle);
        }
    }

    /** @param resource $handle */
    private function groups($handle, LegacyBalanceDataset $dataset): LegacyGroups
    {
        $header = fgetcsv($handle, 0, self::SEPARATOR, '"', '');
        $columns = is_array($header) ? array_flip(array_map(static fn(?string $name): string => trim((string) $name), $header)) : [];

        if (!isset($columns[self::ORGANIZATION_ID])) {
            throw new \InvalidArgumentException(sprintf('El fichero de grupos no tiene la columna "%s".', self::ORGANIZATION_ID));
        }

        $assignments = [];

        while (false !== ($row = fgetcsv($handle, 0, self::SEPARATOR, '"', ''))) {
            $organizationId = $this->value($row, $columns, self::ORGANIZATION_ID);

            if (null === $organizationId) {
                continue;
            }

            if (!$dataset->organizationById($organizationId) instanceof LegacyOrganization) {
                throw new \InvalidArgumentException(sprintf('La organización "%s" del fichero de grupos no existe.', $organizationId));
            }

            if (isset($assignments[$organizationId])) {
                throw new \InvalidArgumentException(sprintf('La organización "%s" está repetida en el fichero de grupos.', $organizationId));
            }

            $latitude = $this->value($row, $columns, 'latitude');
            $longitude = $this->value($row, $columns, 'longitude');

            if ((null === $latitude) !== (null === $longitude)) {
                throw new \InvalidArgumentException(sprintf('La organización "%s" del fichero de grupos tiene solo una coordenada.', $organizationId));
            }

            if ((null !== $latitude && !is_numeric($latitude)) || (null !== $longitude && !is_numeric($longitude))) {
                throw new \InvalidArgumentException(sprintf('Las coordenadas de la organización "%s" del fichero de grupos no son numéricas.', $organizationId));
            }

            $assignments[$organizationId] = new LegacyGroupAssignment(
                organizationId: $organizationId,
                group: $this->value($row, $columns, 'group'),
                bioscoreHotelId: $this->value($row, $columns, 'bioscore_hotel_id'),
                latitude: $latitude,
                longitude: $longitude,
                address: $this->value($row, $columns, 'address'),
                postalCode: $this->value($row, $columns, 'postal_code'),
            );
        }

        $this->denyBioscoreHotelsInSeveralGroups($assignments);

        return new LegacyGroups($assignments);
    }

    /**
     * @param array<int, string|null> $row
     * @param array<string, int>      $columns
     */
    private function value(array $row, array $columns, string $column): ?string
    {
        if (!isset($columns[$column])) {
            return null;
        }

        $value = trim((string) ($row[$columns[$column]] ?? ''));

        return '' !== $value ? $value : null;
    }

    /** @param array<string, LegacyGroupAssignment> $assignments */
    private function denyBioscoreHotelsInSeveralGroups(array $assignments): void
    {
        $groups = [];

        foreach ($assignments as $assignment) {
            if (null === $assignment->bioscoreHotelId) {
                continue;
            }

            if (array_key_exists($assignment->bioscoreHotelId, $groups) && self::accountKey($groups[$assignment->bioscoreHotelId]) !== self::accountKey($assignment)) {
                throw new \InvalidArgumentException(sprintf('El hotel de Bioscore "%s" está en dos grupos: "%s" y "%s".', $assignment->bioscoreHotelId, self::groupLabel($groups[$assignment->bioscoreHotelId]), self::groupLabel($assignment)));
            }

            $groups[$assignment->bioscoreHotelId] = $assignment;
        }
    }

    private static function accountKey(LegacyGroupAssignment $assignment): string
    {
        return $assignment->group ?? $assignment->organizationId;
    }

    private static function groupLabel(LegacyGroupAssignment $assignment): string
    {
        return $assignment->group ?? sprintf('sin grupo (%s)', $assignment->organizationId);
    }
}
