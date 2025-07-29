<?php

namespace App\Tests\Unit\Property\Application\Country\Import;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Application\Country\CountryAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Property\Application\Country\Import\CountryRequest;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Property\Application\Country\Update\UpdateCountryService;
use App\Property\Application\Country\Import\ImportCountriesRequest;
use App\Property\Application\Country\Import\ImportCountriesService;
use App\Property\Application\Country\Import\ImportCountriesResponse;
use App\Property\Application\Country\Register\RegisterCountryService;
use App\Property\Application\Country\Register\RegisterCountryResponse;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;

class ImportCountriesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = CountryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $spain = CountryMother::fromName('España');
        $portugal = CountryMother::fromName('Portugal');
        $england = CountryMother::fromName('Inglaterra');

        $repository->add($spain);
        $repository->add($england);
        $repository->add($portugal);
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());
        $repository->add(CountryMother::random());

        $request = new ImportCountriesRequest(
            [
                new CountryRequest(
                    name: 'españa', 
                    createIfNotExists: false,
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '1234')
                ),
                new CountryRequest(
                    name: 'INGLATERRA', 
                    createIfNotExists: false,
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '234')
                ),
                new CountryRequest(
                    name: 'Irlanda', 
                    createIfNotExists: false,
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '34')
                ),
                new CountryRequest(
                    name: 'Argentina', 
                    createIfNotExists: true,
                    source: ExternalSource::Bioscore,
                    externalId: ExternalId::fromSourceAndId(ExternalSource::Bioscore, '4')
                ),
            ]
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ImportCountriesResponse);

        $this->assertCount(4, $result->countries);
        $this->assertEquals(3, $result->matched);

        $this->assertNotNull($result->countries[0]->countryId);
        $this->assertNotNull($result->countries[1]->countryId);
        $this->assertNull($result->countries[2]->countryId);
        $this->assertNotNull($result->countries[3]->countryId);

        $this->assertEquals('1234', $spain->externalId()->bioscore());
        $this->assertEquals('234', $england->externalId()->bioscore());
    }

    private function createSUT($repository)
    {
        $registerService = $this->createMock(RegisterCountryService::class);
        $registerService->method('__invoke')->willReturn(new RegisterCountryResponse(CountryId::generate(), '', null));

        /** @var RegisterCountryService $registerService */

        $authorizer = new CountryAuthorizer();

        $updateService = new UpdateCountryService(
            $repository, $authorizer
        );

        return new ImportCountriesService(
            repository: $repository, 
            registerCountry: $registerService,
            updateCountry: $updateService,
            authorizer: $authorizer
        );
    }
}