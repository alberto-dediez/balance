<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryConfirmed;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryNotDisqualifiableException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryNotPendingPaymentException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRegistered;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ContestEntryTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterLeavesPendingAndNormalizesEmail(): void
    {
        $entry = $this->register(email: ' Ana.Garcia @Example.COM ');

        $this->assertInstanceOf(ContestEntryRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertSame(ContestEntryStatus::PendingPayment, $entry->status());
        $this->assertSame('ana.garcia@example.com', $entry->email());
        $this->assertNull($entry->entryNumber());
    }

    public function testRegisterRejectsInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(email: 'no-es-un-email');
    }

    public function testRegisterRejectsEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(name: ' ');
    }

    public function testRegisterRejectsCountryNotInAlpha2(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register(country: 'ESP');
    }

    public function testConfirmIsIdempotent(): void
    {
        $entry = $this->register();

        $entry->confirm(1, new \DateTimeImmutable('NOW'));
        $entry->confirm(7, new \DateTimeImmutable('NOW'));

        $this->assertTrue($entry->isConfirmed());
        $this->assertSame(1, $entry->entryNumber());
        $this->assertCount(1, array_filter($this->spySubscriber->events(), static fn(object $event): bool => $event instanceof ContestEntryConfirmed));
    }

    public function testAwardRequiresConfirmed(): void
    {
        $this->expectException(\DomainException::class);

        $this->register()->award(new \DateTimeImmutable('NOW'));
    }

    public function testPurgeKeepsNumberStatusAndWinnerAndClearsData(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 3, winner: true);

        $entry->purgePersonalData(new \DateTimeImmutable('NOW'));

        $this->assertSame(3, $entry->entryNumber());
        $this->assertTrue($entry->isConfirmed());
        $this->assertTrue($entry->isWinner());
        $this->assertTrue($entry->isPurged());
        $this->assertFalse($entry->isEligible());
        $this->assertSame('', $entry->name());
        $this->assertNull($entry->email());
        $this->assertNull($entry->phone());
        $this->assertNull($entry->country());
        $this->assertInstanceOf(ConsentProof::class, $entry->consentProof());
        $this->assertNull($entry->consentProof()->ip());
        $this->assertNull($entry->consentProof()->userAgent());
        $this->assertNotNull($entry->consentProof()->policyVersion());
    }

    public function testPurgeClearsDisqualificationReason(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 1);
        $entry->disqualify(UserId::generate(), 'Datos falsos', new \DateTimeImmutable('NOW'));

        $entry->purgePersonalData(new \DateTimeImmutable('NOW'));

        $this->assertTrue($entry->isDisqualified());
        $this->assertNull($entry->disqualificationReason());
    }

    public function testDisqualifyRemovesPrizeAndKeepsTrace(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 1, winner: true);
        $userId = UserId::generate();

        $entry->disqualify($userId, '  Datos falsos ', new \DateTimeImmutable('NOW'));

        $this->assertTrue($entry->isDisqualified());
        $this->assertFalse($entry->isWinner());
        $this->assertFalse($entry->isEligible());
        $this->assertSame(1, $entry->entryNumber());
        $this->assertSame($userId, $entry->disqualifiedBy());
        $this->assertSame('Datos falsos', $entry->disqualificationReason());
        $this->assertNotNull($entry->disqualifiedAt());
    }

    public function testDisqualifyRequiresReason(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 1);

        $this->expectException(\InvalidArgumentException::class);

        $entry->disqualify(UserId::generate(), ' ', new \DateTimeImmutable('NOW'));
    }

    public function testDisqualifyPendingFails(): void
    {
        $this->expectException(ContestEntryNotDisqualifiableException::class);

        $this->register()->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
    }

    public function testDisqualifyPurgedFails(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 1);
        $entry->purgePersonalData(new \DateTimeImmutable('NOW'));

        $this->expectException(ContestEntryNotDisqualifiableException::class);

        $entry->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
    }

    public function testDisqualifyTwiceFails(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 1);
        $entry->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));

        $this->expectException(ContestEntryNotDisqualifiableException::class);

        $entry->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
    }

    public function testRenewPendingPaymentReplacesContributionDataAndConsent(): void
    {
        $entry = $this->register(originId: 'calc-1', projectContributionId: 'contribution-1');
        $id = $entry->id();
        $consent = new ConsentProof(new \DateTimeImmutable('+1 minute'), 'v2', 'en', '10.0.0.1');

        $entry->renewPendingPayment('contribution-2', 'Ana López', 'ANA@example.com', null, 'pt', Locale::English, $consent, new \DateTimeImmutable('+1 minute'));

        $this->assertSame($id, $entry->id());
        $this->assertSame('calc-1', $entry->originId());
        $this->assertSame('contribution-2', $entry->projectContributionId());
        $this->assertSame('Ana López', $entry->name());
        $this->assertSame('ana@example.com', $entry->email());
        $this->assertSame('PT', $entry->country());
        $this->assertSame(Locale::English, $entry->locale());
        $this->assertSame($consent, $entry->consentProof());
        $this->assertTrue($entry->isPendingPayment());
    }

    public function testRenewPendingPaymentOnConfirmedFails(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 1);

        $this->expectException(ContestEntryNotPendingPaymentException::class);

        $entry->renewPendingPayment('contribution-2', 'Ana', 'ana@example.com', null, 'ES', Locale::Spanish, new ConsentProof(new \DateTimeImmutable('NOW'), 'v', 'es'), new \DateTimeImmutable('NOW'));
    }

    public function testRenewPendingPaymentOnDisqualifiedFails(): void
    {
        $entry = ContestEntryMother::confirmed(ContestMother::published(), 1);
        $entry->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));

        $this->expectException(ContestEntryNotPendingPaymentException::class);

        $entry->renewPendingPayment('contribution-2', 'Ana', 'ana@example.com', null, 'ES', Locale::Spanish, new ConsentProof(new \DateTimeImmutable('NOW'), 'v', 'es'), new \DateTimeImmutable('NOW'));
    }

    private function register(
        string $name = 'Ana García',
        string $email = 'ana@example.com',
        string $originId = 'calc-1',
        ?string $projectContributionId = null,
        string $country = 'ES',
    ): ContestEntry {
        $contest = ContestMother::published();
        $entry = new ContestEntry(ContestEntryId::generate());
        $entry->register(
            contestId: $contest->id(),
            accountId: AccountId::generate(),
            host: ContestMother::eventHost(),
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originId: $originId,
            projectContributionId: $projectContributionId,
            name: $name,
            email: $email,
            phone: null,
            country: $country,
            locale: Locale::Spanish,
            consentProof: ContestEntryMother::consentProof($contest),
            at: new \DateTimeImmutable('NOW'),
        );

        return $entry;
    }
}
