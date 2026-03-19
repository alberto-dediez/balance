<?php

namespace App\Tests\Unit\Property\Application\Destination\Import;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Property\Application\Destination\Import\DestinationRequest;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Property\Application\Destination\Update\UpdateDestinationService;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Property\Application\Destination\Import\ImportDestinationsRequest;
use App\Property\Application\Destination\Import\ImportDestinationsService;
use App\Property\Application\Destination\Import\ImportDestinationsResponse;
use App\Property\Application\Destination\Register\RegisterDestinationService;
use App\Property\Application\Destination\Register\RegisterDestinationResponse;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryMother;

class ImportDestinationsServiceTest extends TestCase
{
    protected function setUp(): void
    {

    }

    public function testInvoke(): void
    {
        $repository = DestinationRepositoryMother::inMemory();
        $countryRepository = CountryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, $countryRepository);

        $spain = CountryMother::fromName('España');
        $countryRepository->add($spain);

        $portugal = CountryMother::fromName('Portugal');
        $countryRepository->add($portugal);

        $barcelona = DestinationMother::fromNameAndCountry('Barcelona', (string) $spain->id());
        $madrid = DestinationMother::fromNameAndCountry('Madrid', (string) $spain->id());
        $otherBarcelona = DestinationMother::fromNameAndCountry('Barcelona', (string) $portugal->id());

        $repository->add($barcelona);
        $repository->add($madrid);
        $repository->add($otherBarcelona);
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());

        $request = new ImportDestinationsRequest(
            [
                new DestinationRequest(
                    name: 'barcelona',
                    countryId: $spain->id(),
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '1234'),
                    createIfNotExists: false
                ),
                new DestinationRequest(
                    name: 'MADRID',
                    countryId: $spain->id(),
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '2345'),
                    createIfNotExists: false
                ),
                new DestinationRequest(
                    name: 'cordoba',
                    countryId: $spain->id(),
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '3456'),
                    createIfNotExists: false
                ),
                new DestinationRequest(
                    name: 'oporto',
                    countryId: $portugal->id(),
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '4567'),
                    createIfNotExists: true
                ),
            ]
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ImportDestinationsResponse);

        $this->assertCount(4, $result->destinations);
        $this->assertEquals(3, $result->matched);

        $this->assertNotNull($result->destinations[0]->destinationId);
        $this->assertTrue($barcelona->id()->equals($result->destinations[0]->destinationId));
        $this->assertNotNull($result->destinations[1]->destinationId);
        $this->assertNull($result->destinations[2]->destinationId);
        $this->assertNotNull($result->destinations[3]->destinationId);

        $this->assertEquals('1234', $barcelona->externalId()->bioscore());
    }

    private function createSUT(InMemoryDestinationRepository $repository, InMemoryCountryRepository $countryRepository): ImportDestinationsService
    {
        $registerService = $this->createMock(RegisterDestinationService::class);
        $registerService->method('__invoke')->willReturn(new RegisterDestinationResponse(DestinationId::generate(), '', CountryId::generate()));

        $authorizer = new DestinationAuthorizer();

        $updateService = new UpdateDestinationService(
            $repository, $authorizer
        );

        /** @var RegisterDestinationService $registerService */

        return new ImportDestinationsService(
            repository: $repository,
            registerDestination: $registerService,
            updateDestination: $updateService,
            authorizer: $authorizer
        );
    }
}
