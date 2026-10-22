<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Api\PropertyRegister;

use App\Property\Application\Brand\Import\BrandRequest;
use App\Property\Application\Brand\Import\ImportBrandsRequest;
use App\Property\Application\Brand\Import\ImportBrandsService;
use App\Property\Application\Category\Import\CategoryRequest;
use App\Property\Application\Category\Import\ImportCategoriesRequest;
use App\Property\Application\Category\Import\ImportCategoriesService;
use App\Property\Application\Country\Import\CountryRequest;
use App\Property\Application\Country\Import\ImportCountriesRequest;
use App\Property\Application\Country\Import\ImportCountriesService;
use App\Property\Application\Destination\Import\DestinationRequest;
use App\Property\Application\Destination\Import\ImportDestinationsRequest;
use App\Property\Application\Destination\Import\ImportDestinationsService;
use App\Property\Application\Property\Register\RegisterPropertyRequest;
use App\Property\Application\Property\Register\RegisterPropertyService;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponse;
use App\Property\Infrastructure\UI\Api\Property\PropertyResponseGenerator;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Nelmio\ApiDocBundle\Attribute\Areas;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/properties', name: 'api_properties_register', methods: ['POST'])]
#[Areas(['admin'])]
#[OA\Post(description: 'Register a new property assigned to an account', summary: 'Register Property', tags: ['Property'])]
#[OA\Response(
    response: 200,
    description: 'Returns data of the registered property',
    content: new Model(type: PropertyResponse::class),
)]
class PropertyRegisterController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload()]
        PropertyRegisterRequest $request,
        RegisterPropertyService $registerProperty,
        PropertyRepository $propertyRepository,
        PropertyResponseGenerator $propertyGenerator,
        ImportCountriesService $importCountries,
        ImportDestinationsService $importDestinations,
        ImportCategoriesService $importCategories,
        ImportBrandsService $importBrands,
    ): JsonResponse {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        $source = null !== $request->source ? ExternalSource::from($request->source) : null;

        $countryId = $this->resolveCountryId($request, $source, $importCountries, $user);
        $destinationId = $this->resolveDestinationId($request, $source, $countryId, $importDestinations, $user);
        $categoryId = $this->resolveCategoryId($request, $source, $importCategories, $user);
        $brandId = $this->resolveBrandId($request, $source, $importBrands, $user);

        $info = new PropertyInfo(
            propertyType: null !== $request->propertyType ? PropertyType::from($request->propertyType) : null,
            categoryId: $categoryId,
            brandId: $brandId,
            address: $request->address,
            postalCode: $request->postalCode,
            longitude: $request->longitude,
            latitude: $request->latitude,
            rooms: $request->rooms,
            zone: $request->zone,
            web: $request->web,
            destinationId: $destinationId,
            countryId: $countryId,
        );

        $externalId = (null !== $source && null !== $request->externalId)
            ? ExternalId::fromSourceAndId($source, $request->externalId)
            : new ExternalId();

        $response = $registerProperty(
            new RegisterPropertyRequest(
                name: $request->name,
                accountId: new AccountId($request->accountId),
                info: $info,
                externalId: $externalId,
                operatingRegime: OperatingRegime::tryFrom((string) $request->operatingRegime) ?? OperatingRegime::Owned,
            ),
            $user,
        );

        $property = $propertyRepository->find($response->propertyId);
        assert($property instanceof Property);

        return $this->json($propertyGenerator->generate($property));
    }

    private function resolveCountryId(
        PropertyRegisterRequest $request,
        ?ExternalSource $source,
        ImportCountriesService $importCountries,
        SecurityUser $user,
    ): ?CountryId {
        if (null === $source || null === $request->country || empty($request->country->name) || empty($request->country->externalId)) {
            return null;
        }

        $externalId = ExternalId::fromSourceAndId($source, $request->country->externalId);

        $response = $importCountries(new ImportCountriesRequest([
            new CountryRequest(
                name: $request->country->name,
                source: $source,
                externalId: $externalId,
                iso: $request->country->iso,
                createIfNotExists: true,
            ),
        ]), $user);

        return $response->countries[0]->countryId ?? null;
    }

    private function resolveDestinationId(
        PropertyRegisterRequest $request,
        ?ExternalSource $source,
        ?CountryId $countryId,
        ImportDestinationsService $importDestinations,
        SecurityUser $user,
    ): ?DestinationId {
        if (null === $source || null === $countryId || null === $request->destination || empty($request->destination->name) || empty($request->destination->externalId)) {
            return null;
        }

        $externalId = ExternalId::fromSourceAndId($source, $request->destination->externalId);

        $response = $importDestinations(new ImportDestinationsRequest([
            new DestinationRequest(
                name: $request->destination->name,
                countryId: $countryId,
                source: $source,
                externalId: $externalId,
                createIfNotExists: true,
            ),
        ]), $user);

        return $response->destinations[0]->destinationId ?? null;
    }

    private function resolveCategoryId(
        PropertyRegisterRequest $request,
        ?ExternalSource $source,
        ImportCategoriesService $importCategories,
        SecurityUser $user,
    ): ?CategoryId {
        if (null === $source || null === $request->category || empty($request->category->name) || empty($request->category->externalId)) {
            return null;
        }

        $externalId = ExternalId::fromSourceAndId($source, $request->category->externalId);

        $response = $importCategories(new ImportCategoriesRequest([
            new CategoryRequest(
                name: $request->category->name,
                source: $source,
                externalId: $externalId,
                createIfNotExists: true,
            ),
        ]), $user);

        return $response->categories[0]->categoryId ?? null;
    }

    private function resolveBrandId(
        PropertyRegisterRequest $request,
        ?ExternalSource $source,
        ImportBrandsService $importBrands,
        SecurityUser $user,
    ): ?BrandId {
        if (null === $source || null === $request->brand || empty($request->brand->name) || empty($request->brand->externalId)) {
            return null;
        }

        $externalId = ExternalId::fromSourceAndId($source, $request->brand->externalId);

        $response = $importBrands(new ImportBrandsRequest([
            new BrandRequest(
                name: $request->brand->name,
                source: $source,
                externalId: $externalId,
                createIfNotExists: true,
            ),
        ]), $user);

        return $response->brands[0]->brandId ?? null;
    }
}
