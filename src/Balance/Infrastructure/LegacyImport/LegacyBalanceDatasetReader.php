<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final class LegacyBalanceDatasetReader
{
    private const string ORGANIZATIONS = 'Organization.json';

    private const string USERS = 'User.json';

    private const string MEMBERSHIPS = 'OrganizationMember.json';

    private const string CALCULATIONS = 'CalculatedEmissions.json';

    private const string DONATIONS = 'Donation.json';

    private const string ECO_PROJECTS = 'EcoProject.json';

    private const string GLOBAL_ADMIN = 'GENERAL_GLOBAL_ADMIN';

    public function read(string $directory): LegacyBalanceDataset
    {
        $files = [self::ORGANIZATIONS, self::USERS, self::MEMBERSHIPS, self::CALCULATIONS, self::DONATIONS, self::ECO_PROJECTS];

        foreach ($files as $file) {
            $path = $this->path($directory, $file);

            if (!file_exists($path)) {
                throw new \RuntimeException(sprintf('El fichero "%s" no existe.', $file));
            }

            if (!is_readable($path)) {
                throw new \RuntimeException(sprintf('El fichero "%s" no se puede leer.', $file));
            }
        }

        return new LegacyBalanceDataset(
            organizations: array_map($this->organization(...), $this->records($directory, self::ORGANIZATIONS)),
            users: array_map($this->user(...), $this->records($directory, self::USERS)),
            memberships: array_map($this->membership(...), $this->records($directory, self::MEMBERSHIPS)),
            calculations: array_map($this->calculation(...), $this->records($directory, self::CALCULATIONS)),
            donations: array_map($this->donation(...), $this->records($directory, self::DONATIONS)),
            ecoProjects: array_map($this->ecoProject(...), $this->records($directory, self::ECO_PROJECTS)),
        );
    }

    /** @return list<array<string, mixed>> */
    private function records(string $directory, string $file): array
    {
        $records = json_decode((string) file_get_contents($this->path($directory, $file)), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($records)) {
            throw new \RuntimeException(sprintf('El fichero "%s" no contiene una lista.', $file));
        }

        return array_values($records);
    }

    private function path(string $directory, string $file): string
    {
        return rtrim($directory, '/') . '/' . $file;
    }

    /** @param array<string, mixed> $record */
    private function organization(array $record): LegacyOrganization
    {
        return new LegacyOrganization(
            id: $this->id($record),
            slug: $this->string($record, 'sluglified_name'),
            displayName: $this->string($record, 'display_name'),
            type: $this->string($record, 'organizationType'),
            active: (bool) ($record['active'] ?? false),
            createdAt: $this->createdAt($record),
        );
    }

    /** @param array<string, mixed> $record */
    private function user(array $record): LegacyUser
    {
        $permissions = $record['global_permissions'] ?? [];

        return new LegacyUser(
            id: $this->id($record),
            email: mb_strtolower(trim($this->string($record, 'email'))),
            globalAdmin: is_array($permissions) && in_array(self::GLOBAL_ADMIN, $permissions, true),
            createdAt: $this->createdAt($record),
        );
    }

    /** @param array<string, mixed> $record */
    private function membership(array $record): LegacyMembership
    {
        $roles = $record['role_names'] ?? [];
        $roles = is_array($roles) ? implode(',', array_map(strval(...), $roles)) : (string) $roles;

        return new LegacyMembership(
            userId: $this->string($record, 'member_id'),
            organizationId: $this->string($record, 'organization_id'),
            role: str_contains($roles, LegacyMembership::ROLE_ADMIN) ? LegacyMembership::ROLE_ADMIN : LegacyMembership::ROLE_EXTERNAL,
            createdAt: $this->createdAt($record),
        );
    }

    /** @param array<string, mixed> $record */
    private function calculation(array $record): LegacyCalculation
    {
        /** @var array<string, mixed> $request */
        $request = is_array($record['request'] ?? null) ? $record['request'] : [];

        /** @var array<string, mixed> $referral */
        $referral = is_array($request['referral'] ?? null) ? $request['referral'] : [];
        $referralSlug = $this->string($referral, 'sluglified_name');

        return new LegacyCalculation(
            id: $this->id($record),
            referralSlug: '' !== $referralSlug ? $referralSlug : null,
            transport: $this->string($request, 'transport'),
            nights: (int) ($request['nights'] ?? 0),
            persons: (int) ($request['persons'] ?? 1),
            roundTrip: (bool) ($request['roundtrip'] ?? false),
            distanceMeters: (float) ($record['distance_in_meters'] ?? 0),
            emissionsTotalGrams: (float) ($record['emissions_total'] ?? 0),
            origin: $this->place($request, 'origin', 'raw_origin'),
            destination: $this->place($request, 'destination', 'raw_destination'),
            createdAt: $this->createdAt($record),
        );
    }

    /** @param array<string, mixed> $request */
    private function place(array $request, string $pointKey, string $rawKey): ?LegacyPlace
    {
        $point = $request[$pointKey] ?? null;

        if (!is_array($point) || !is_numeric($point['latitude'] ?? null) || !is_numeric($point['longitude'] ?? null)) {
            return null;
        }

        /** @var array<string, mixed> $raw */
        $raw = is_array($request[$rawKey] ?? null) ? $request[$rawKey] : [];

        return new LegacyPlace(
            placeId: is_string($raw['place_id'] ?? null) ? $raw['place_id'] : null,
            formattedAddress: is_string($raw['formatted_address'] ?? null) ? $raw['formatted_address'] : null,
            latitude: (float) $point['latitude'],
            longitude: (float) $point['longitude'],
        );
    }

    /** @param array<string, mixed> $record */
    private function donation(array $record): LegacyDonation
    {
        return new LegacyDonation(
            transactionUuid: $this->string($record, 'transaction_uuid'),
            organizationId: $this->string($record, 'organizationId'),
            ecoProjectId: $this->string($record, 'ecoProjectId'),
            amount: (float) ($record['amount'] ?? 0),
            currency: $this->string($record, 'currency'),
            donerName: $this->string($record, 'donerName'),
            donerEmail: mb_strtolower(trim($this->string($record, 'donerEmail'))),
            createdAt: $this->createdAt($record),
        );
    }

    /** @param array<string, mixed> $record */
    private function ecoProject(array $record): LegacyEcoProject
    {
        return new LegacyEcoProject(
            id: $this->id($record),
            displayName: $this->string($record, 'display_name'),
            slug: $this->string($record, 'sluglified_name'),
        );
    }

    /** @param array<string, mixed> $record */
    private function id(array $record): string
    {
        $id = $record['_id'] ?? null;

        return is_array($id) ? (string) ($id['$oid'] ?? '') : (string) $id;
    }

    /** @param array<string, mixed> $record */
    private function createdAt(array $record): \DateTimeImmutable
    {
        $createdAt = $record['created_at'] ?? null;
        $value = is_array($createdAt) ? (string) ($createdAt['$date'] ?? '') : (string) $createdAt;

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.v\Z', $value, new \DateTimeZone('UTC'));

        if (false === $date) {
            throw new \RuntimeException(sprintf('Fecha no válida: "%s".', $value));
        }

        return $date;
    }

    /** @param array<string, mixed> $record */
    private function string(array $record, string $key): string
    {
        $value = $record[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }
}
