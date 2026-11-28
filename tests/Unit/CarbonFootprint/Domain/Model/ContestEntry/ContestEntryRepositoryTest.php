<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryFilter;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryStatus;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Contest\ContestMother;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ContestEntryRepositoryTest extends KernelTestCase
{
    public function testAddFindAndRemove(): void
    {
        $repository = $this->createSUT();
        $entry = ContestEntryMother::pending($this->contest());

        $repository->add($entry);

        $this->assertInstanceOf(ContestEntry::class, $repository->find($entry->id()));

        $repository->remove($entry);

        $this->assertNull($repository->find($entry->id()));
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $repository = $this->createSUT();
        $contest = $this->contest();
        $repository->add(ContestEntryMother::pending($contest, email: 'ana@example.com'));

        $this->expectException(ContestEntryAlreadyExistsException::class);

        $repository->add(ContestEntryMother::pending($contest, email: 'ana@example.com'));
    }

    public function testFindOneByOriginAndEmail(): void
    {
        $repository = $this->createSUT();
        $contest = $this->contest();
        $entry = ContestEntryMother::pending($contest, email: 'ana@example.com', originId: 'calc-1');
        $repository->add($entry);

        $this->assertTrue($repository->findOneByOrigin($contest->id(), ProjectContributionOrigin::AttendeeSurveyResponse, 'calc-1')?->id()->equals($entry->id()));
        $this->assertNull($repository->findOneByOrigin($contest->id(), ProjectContributionOrigin::TripCalculation, 'calc-1'));
        $this->assertTrue($repository->findOneByEmail($contest->id(), 'ANA@example.com')?->id()->equals($entry->id()));
        $this->assertNull($repository->findOneByEmail($this->contest()->id(), 'ana@example.com'));
    }

    public function testFindByProjectContributionId(): void
    {
        $repository = $this->createSUT();
        $contest = $this->contest();
        $repository->add(ContestEntryMother::pending($contest, projectContributionId: 'pc-1'));
        $repository->add(ContestEntryMother::pending($contest, projectContributionId: 'pc-2'));

        $this->assertCount(1, $repository->findByProjectContributionId('pc-1'));
    }

    public function testEligibleWithoutPrizeAndWinners(): void
    {
        $repository = $this->createSUT();
        $contest = $this->contest();
        $winner = ContestEntryMother::confirmed($contest, 1, winner: true);
        $third = ContestEntryMother::confirmed($contest, 3);
        $second = ContestEntryMother::confirmed($contest, 2);
        $disqualified = ContestEntryMother::confirmed($contest, 4);
        $disqualified->disqualify(UserId::generate(), 'Motivo', new \DateTimeImmutable('NOW'));
        $purged = ContestEntryMother::confirmed($contest, 5);
        $purged->purgePersonalData(new \DateTimeImmutable('NOW'));

        foreach ([$winner, $third, $second, $disqualified, $purged, ContestEntryMother::pending($contest)] as $entry) {
            $repository->add($entry);
        }

        $this->assertSame(
            [(string) $second->id(), (string) $third->id()],
            array_map(static fn(ContestEntryId $id): string => (string) $id, $repository->findEligibleWithoutPrizeIds($contest->id())),
        );
        $this->assertTrue($repository->findNextEligibleWithoutPrize($contest->id())?->id()->equals($second->id()));
        $this->assertSame(1, $repository->countWinners($contest->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();
        $contest = $this->contest();
        $repository->add(ContestEntryMother::confirmed($contest, 2, email: 'bea@example.com'));
        $repository->add(ContestEntryMother::confirmed($contest, 1, winner: true, email: 'ana@example.com'));
        $repository->add(ContestEntryMother::pending($contest, email: 'carla@example.com'));
        $repository->add(ContestEntryMother::confirmed($this->contest(), 1));

        $all = $repository->findByFilter(new ContestEntryFilter(contestId: $contest->id()));

        $this->assertCount(3, $all);
        $this->assertSame(1, $all[1]->entryNumber());
        $this->assertSame(3, $repository->countByFilter(new ContestEntryFilter(contestId: $contest->id())));
        $this->assertSame(2, $repository->countByFilter(new ContestEntryFilter(contestId: $contest->id(), status: ContestEntryStatus::Confirmed)));
        $this->assertSame(1, $repository->countByFilter(new ContestEntryFilter(contestId: $contest->id(), winner: true)));
        $this->assertSame(1, $repository->countByFilter(new ContestEntryFilter(contestId: $contest->id(), text: 'bea@')));
    }

    protected function contest(): Contest
    {
        $contest = ContestMother::published(hosts: [ContestMother::eventHost((string) UserId::generate())]);
        $this->contestRepository()?->add($contest);

        return $contest;
    }

    protected function contestRepository(): ?ContestRepository
    {
        return null;
    }

    abstract protected function createSUT(): ContestEntryRepository;
}
