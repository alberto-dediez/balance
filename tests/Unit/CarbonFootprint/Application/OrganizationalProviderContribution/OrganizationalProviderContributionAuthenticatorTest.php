<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;

class OrganizationalProviderContributionAuthenticatorTest extends OrganizationalProviderContributionPublicServiceTestCase
{
    public function testAuthenticatesTheContributionAndItsProviderLinkedUser(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->linkedProvider();
        $contribution = $this->requestedContribution((string) $footprint->id(), $provider);

        $authentication = $this->authenticator->authenticate($contribution->token());

        $this->assertSame($contribution, $authentication->contribution);
        $this->assertEquals($provider->userId(), $authentication->user->id());
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(OrganizationalProviderContributionTokenInvalidException::class);

        $this->authenticator->authenticate('invalid-token');
    }

    public function testIgnoredContributionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());
        $contribution->ignore();

        $this->expectException(OrganizationalProviderContributionIgnoredException::class);

        $this->authenticator->authenticate($contribution->token());
    }

    public function testManualContributionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->linkedProvider();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: (string) $provider->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalProviderContributionTokenInvalidException::class);

        $this->authenticator->authenticate($contribution->token());
    }

    public function testProviderWithoutLinkedUserThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->provider(linked: false));

        $this->expectException(OrganizationalProviderContributionTokenInvalidException::class);

        $this->authenticator->authenticate($contribution->token());
    }

    public function testMissingCarbonFootprintThrowsOnLocate(): void
    {
        $contribution = $this->requestedContribution('missing-footprint-id', $this->linkedProvider());
        $authentication = $this->authenticator->authenticate($contribution->token());

        $this->expectException(OrganizationalProviderContributionTokenInvalidException::class);

        $this->authenticator->locateCarbonFootprint($authentication);
    }
}
