<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\UI\Web\Webhook;

use App\Security\Application\EmailLog\Track\TrackEmailEventService;
use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryEmailLogRepository;
use App\Security\Infrastructure\UI\Web\Webhook\SendgridWebhookController;
use App\Security\Infrastructure\Webhook\SendgridSignatureVerifier;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogMother;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogRepositoryMother;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SendgridWebhookControllerTest extends TestCase
{
    private InMemoryEmailLogRepository $repository;

    private EmailLog $emailLog;

    private SendgridWebhookController $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = EmailLogRepositoryMother::inMemory();
        $this->emailLog = EmailLogMother::sent();
        $this->repository->add($this->emailLog);

        $this->sut = $this->controller('');
    }

    public function testDeliveredMarksAsDelivered(): void
    {
        $response = $this->post([$this->event('delivered')]);

        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertEquals(EmailLogStatus::Delivered, $this->stored()->status());
        $this->assertEquals(new \DateTimeImmutable('@1700000000'), $this->stored()->deliveredAt());
    }

    public function testProcessedChangesNothing(): void
    {
        $response = $this->post([$this->event('processed')]);

        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertEquals(EmailLogStatus::Sent, $this->stored()->status());
        $this->assertNull($this->stored()->lastEventAt());
    }

    public function testOpenIncreasesCounter(): void
    {
        $this->post([$this->event('open')]);

        $this->assertEquals(1, $this->stored()->openCount());
    }

    public function testClickIncreasesCounter(): void
    {
        $this->post([$this->event('click')]);

        $this->assertEquals(1, $this->stored()->clickCount());
    }

    public function testEventWithoutEmailLogIdIsIgnored(): void
    {
        $event = $this->event('delivered');
        unset($event['email_log_id']);

        $response = $this->post([$event]);

        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertEquals(EmailLogStatus::Sent, $this->stored()->status());
    }

    public function testBounceStoresReason(): void
    {
        $this->post([$this->event('bounce', ['reason' => '550 unknown user'])]);

        $this->assertEquals(EmailLogStatus::Bounced, $this->stored()->status());
        $this->assertEquals('550 unknown user', $this->stored()->statusReason());
    }

    public function testDeferredStoresResponseAsReason(): void
    {
        $this->post([$this->event('deferred', ['response' => '421 try again later'])]);

        $this->assertEquals(EmailLogStatus::Deferred, $this->stored()->status());
        $this->assertEquals('421 try again later', $this->stored()->statusReason());
    }

    public function testUnknownEventIsIgnoredWithoutRejectingTheBatch(): void
    {
        $response = $this->post([$this->event('group_unsubscribe'), $this->event('delivered')]);

        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertEquals(EmailLogStatus::Delivered, $this->stored()->status());
    }

    public function testFailingEventDoesNotStopTheBatch(): void
    {
        $response = $this->post([
            $this->event('delivered', ['email_log_id' => 'not-a-uuid']),
            $this->event('open'),
        ]);

        $this->assertEquals(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertEquals(1, $this->stored()->openCount());
    }

    public function testMalformedPayloadIsRejected(): void
    {
        $event = $this->event('delivered');
        unset($event['sg_event_id']);

        $response = $this->post([$event]);

        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        $this->assertEquals(EmailLogStatus::Sent, $this->stored()->status());
    }

    public function testNonJsonBodyIsRejected(): void
    {
        $response = ($this->sut)(Request::create('/webhook/sendgrid', 'POST', content: 'not json'));

        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
    }

    public function testMissingSignatureIsRejectedWhenSecretIsConfigured(): void
    {
        $privateKey = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->assertNotFalse($privateKey);
        $details = openssl_pkey_get_details($privateKey);
        $this->assertNotFalse($details);
        $publicKey = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n"], '', (string) $details['key']);

        $this->sut = $this->controller($publicKey);

        $response = $this->post([$this->event('delivered')]);

        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        $this->assertEquals(EmailLogStatus::Sent, $this->stored()->status());
    }

    public function testEmptySecretWithSendgridProviderIsRejected(): void
    {
        $this->sut = $this->controller('', 'sendgrid://KEY@default');

        $response = $this->post([$this->event('delivered')]);

        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        $this->assertEquals(EmailLogStatus::Sent, $this->stored()->status());
    }

    private function controller(string $secret, string $mailerDsn = 'smtp://mailer:1025'): SendgridWebhookController
    {
        return new SendgridWebhookController(
            new TrackEmailEventService($this->repository),
            new SendgridSignatureVerifier($secret, $mailerDsn),
            new NullLogger(),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $events
     */
    private function post(array $events): Response
    {
        return ($this->sut)(Request::create('/webhook/sendgrid', 'POST', content: (string) json_encode($events)));
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function event(string $rawEvent, array $extra = []): array
    {
        return array_merge([
            'email' => 'recipient@example.com',
            'timestamp' => 1700000000,
            'event' => $rawEvent,
            'sg_event_id' => 'e1',
            'sg_message_id' => 'm1',
            'email_log_id' => (string) $this->emailLog->id(),
        ], $extra);
    }

    private function stored(): EmailLog
    {
        $emailLog = $this->repository->find($this->emailLog->id());
        $this->assertNotNull($emailLog);

        return $emailLog;
    }
}
