<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\Assistant;

use App\Security\Application\SignatureRequest\Assistant\ListSignatureRequestsTool;
use App\Security\Application\SignatureRequest\List\ListSignatureRequestsService;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemorySignatureRequestRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListSignatureRequestsToolTest extends TestCase
{
    private InMemorySignatureRequestRepository $signatureRequests;

    private ListSignatureRequestsTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->signatureRequests = SignatureRequestRepositoryMother::inMemory();
        $authorizer = new SignatureRequestAuthorizer();
        $this->sut = new ListSignatureRequestsTool(new ListSignatureRequestsService($this->signatureRequests, $authorizer), $authorizer);
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('list_signature_requests', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testListsTheSignatureRequestsOfAnAccount(): void
    {
        $accountId = (string) AccountId::generate();
        $signatureRequest = SignatureRequestMother::fromAccountId($accountId);
        $this->signatureRequests->add($signatureRequest);
        $this->signatureRequests->add(SignatureRequestMother::fromAccountId((string) AccountId::generate()));

        $result = $this->sut->execute($this->arguments(['accountId' => $accountId, 'status' => 'Pending']), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame((string) $signatureRequest->id(), $result->data['signatureRequests'][0]['id']);
        $this->assertSame($accountId, $result->data['signatureRequests'][0]['accountId']);
        $this->assertSame('Pending', $result->data['signatureRequests'][0]['status']);
        $this->assertSame('route:admin_signature_requests_show?signatureRequestId=' . $signatureRequest->id(), $result->data['signatureRequests'][0]['href']);
        $this->assertSame(['id', 'href', 'accountId', 'status', 'registeredAt', 'updatedAt', 'expiresAt'], array_keys($result->data['signatureRequests'][0]));
        $this->assertSame('admin_signature_requests', $result->link?->route);
    }

    public function testReturnsAtMostTheMaximumNumberOfItems(): void
    {
        for ($i = 0; $i < ToolResult::ITEMS_MAX_COUNT + 5; ++$i) {
            $this->signatureRequests->add(SignatureRequestMother::random());
        }

        $result = $this->sut->execute($this->arguments(['limit' => 50]), UserMother::adminUser(), $this->context());

        $this->assertCount(ToolResult::ITEMS_MAX_COUNT, $result->data['signatureRequests']);
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-16 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
