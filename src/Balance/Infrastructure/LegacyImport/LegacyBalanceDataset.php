<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyBalanceDataset
{
    /**
     * @param list<LegacyOrganization> $organizations
     * @param list<LegacyUser>         $users
     * @param list<LegacyMembership>   $memberships
     * @param list<LegacyCalculation>  $calculations
     * @param list<LegacyDonation>     $donations
     * @param list<LegacyEcoProject>   $ecoProjects
     */
    public function __construct(
        public array $organizations,
        public array $users,
        public array $memberships,
        public array $calculations,
        public array $donations,
        public array $ecoProjects,
    ) {}

    public function organizationBySlug(string $slug): ?LegacyOrganization
    {
        foreach ($this->organizations as $organization) {
            if ($organization->slug === $slug) {
                return $organization;
            }
        }

        return null;
    }

    public function organizationById(string $id): ?LegacyOrganization
    {
        foreach ($this->organizations as $organization) {
            if ($organization->id === $id) {
                return $organization;
            }
        }

        return null;
    }

    public function userById(string $id): ?LegacyUser
    {
        foreach ($this->users as $user) {
            if ($user->id === $id) {
                return $user;
            }
        }

        return null;
    }

    public function ecoProjectById(string $id): ?LegacyEcoProject
    {
        foreach ($this->ecoProjects as $ecoProject) {
            if ($ecoProject->id === $id) {
                return $ecoProject;
            }
        }

        return null;
    }

    /** @return list<LegacyMembership> */
    public function membershipsOfUser(string $userId): array
    {
        $memberships = array_values(array_filter(
            $this->memberships,
            static fn(LegacyMembership $membership): bool => $membership->userId === $userId,
        ));

        usort($memberships, static fn(LegacyMembership $a, LegacyMembership $b): int => $a->createdAt <=> $b->createdAt);

        return $memberships;
    }

    /** @return list<LegacyCalculation> */
    public function calculationsOfOrganization(string $slug): array
    {
        return array_values(array_filter(
            $this->calculations,
            static fn(LegacyCalculation $calculation): bool => $calculation->referralSlug === $slug,
        ));
    }

    /** @return list<LegacyDonation> */
    public function donationsOfOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->donations,
            static fn(LegacyDonation $donation): bool => $donation->organizationId === $organizationId,
        ));
    }
}
