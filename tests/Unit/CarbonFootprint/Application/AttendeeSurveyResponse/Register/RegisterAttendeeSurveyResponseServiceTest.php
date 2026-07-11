<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseRequest;
use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosedException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use PHPUnit\Framework\TestCase;

class RegisterAttendeeSurveyResponseServiceTest extends TestCase
{
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;
    private InMemoryAttendeeSurveyResponseRepository $attendeeSurveyResponseRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private RegisterAttendeeSurveyResponseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->attendeeSurveyResponseRepository = new InMemoryAttendeeSurveyResponseRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();

        $this->sut = new RegisterAttendeeSurveyResponseService(
            $this->attendeeSurveyRepository,
            $this->attendeeSurveyResponseRepository,
            $this->mobilityRepository,
        );
    }

    public function testRegistersAResponseWithBothBlocks(): void
    {
        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $mobility = MobilityMother::registeredMobility();
        $this->mobilityRepository->add($mobility);

        $response = ($this->sut)(new RegisterAttendeeSurveyResponseRequest(
            token: $attendeeSurvey->token(),
            cameFromAnotherCity: true,
            intercityDays: 2,
            intercityMobilityId: (string) $mobility->id(),
            intercityVehicleOccupants: 1,
            intercityDistanceKm: 300.0,
            usedHotel: true,
            hotelNights: 2,
            cityDays: 3,
            cityMobilityId: (string) $mobility->id(),
            cityVehicleOccupants: 1,
            cityDistanceKm: 10.0,
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);
        $this->assertNotNull($stored);
        $this->assertTrue($stored->cameFromAnotherCity());
        $this->assertSame(2, $stored->intercityDays());
        $this->assertSame(3, $stored->cityDays());
        $this->assertTrue($stored->usedHotel());
        $this->assertSame(2, $stored->hotelNights());
    }

    public function testThrowsOnInvalidToken(): void
    {
        $this->expectException(AttendeeSurveyTokenInvalidException::class);

        ($this->sut)($this->request(token: 'invalid-token'));
    }

    public function testThrowsWhenSurveyIsClosed(): void
    {
        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();
        $attendeeSurvey->close(10, ConfidenceLevel::Ninety, 0.1);
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $this->expectException(AttendeeSurveyClosedException::class);

        ($this->sut)($this->request(token: $attendeeSurvey->token()));
    }

    public function testThrowsWhenBothBlocksAreEmpty(): void
    {
        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(token: $attendeeSurvey->token(), cameFromAnotherCity: false, intercityMobilityId: null, cityMobilityId: null));
    }

    public function testThrowsWhenMobilityDoesNotExistInCatalog(): void
    {
        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            cameFromAnotherCity: true,
            intercityDays: 1,
            intercityMobilityId: 'unknown-mobility-id',
            intercityVehicleOccupants: 1,
            intercityDistanceKm: 10.0,
        ));
    }

    public function testThrowsWhenHotelUsedWithoutNights(): void
    {
        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $mobility = MobilityMother::registeredMobility();
        $this->mobilityRepository->add($mobility);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            cameFromAnotherCity: true,
            intercityDays: 1,
            intercityMobilityId: (string) $mobility->id(),
            intercityVehicleOccupants: 1,
            intercityDistanceKm: 10.0,
            usedHotel: true,
            hotelNights: null,
        ));
    }

    private function request(
        string $token,
        bool $cameFromAnotherCity = true,
        ?int $intercityDays = 1,
        ?string $intercityMobilityId = 'mobility-id',
        ?int $intercityVehicleOccupants = 1,
        ?float $intercityDistanceKm = 10.0,
        bool $usedHotel = false,
        ?int $hotelNights = null,
        ?int $cityDays = null,
        ?string $cityMobilityId = null,
        ?int $cityVehicleOccupants = null,
        ?float $cityDistanceKm = null,
    ): RegisterAttendeeSurveyResponseRequest {
        return new RegisterAttendeeSurveyResponseRequest(
            token: $token,
            cameFromAnotherCity: $cameFromAnotherCity,
            intercityDays: $intercityDays,
            intercityMobilityId: $intercityMobilityId,
            intercityVehicleOccupants: $intercityVehicleOccupants,
            intercityDistanceKm: $intercityDistanceKm,
            usedHotel: $usedHotel,
            hotelNights: $hotelNights,
            cityDays: $cityDays,
            cityMobilityId: $cityMobilityId,
            cityVehicleOccupants: $cityVehicleOccupants,
            cityDistanceKm: $cityDistanceKm,
        );
    }
}
