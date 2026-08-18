<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Security;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\OtpChallenge;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\SignatureRequest\Signer;
use App\Security\Domain\Model\SignatureRequest\Token;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Hook\AfterScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;

use function PHPUnit\Framework\assertNotEmpty;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SecurityContext implements Context
{
    private AcceptanceClient $client;

    private string $lastOtpCode = '';

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    #[AfterScenario]
    public function after(AfterScenarioScope $scope): void
    {
        $this->client->reset();
    }

    private function getUserRepository(): UserRepository
    {
        return $this->client->getContainer()->get(UserRepository::class);
    }

    private function getAccountRepository(): AccountRepository
    {
        return $this->client->getContainer()->get(AccountRepository::class);
    }

    private function getSignatureRequestRepository(): SignatureRequestRepository
    {
        return $this->client->getContainer()->get(SignatureRequestRepository::class);
    }

    private function getRecordedEventRepository(): RecordedEventRepository
    {
        return $this->client->getContainer()->get(RecordedEventRepository::class);
    }

    #[Given('user :username with role :role exists')]
    public function userWithRoleExists(string $username, string $role): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $user = UserMother::fromUsernameAndRole($username, Role::tryFrom($role));

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with id :id exists')]
    public function userWithIdExists(string $username, string $id): void
    {
        $user = UserMother::fromUsernameAndRoleAndId($username, Role::User, new UserId($id));

        $this->getUserRepository()->add($user);
    }

    #[Given('account :name with id :id exists')]
    public function accountWithIdExists(string $username, string $id): void
    {
        $account = AccountMother::fromNameAndId($username, $id);

        $this->getAccountRepository()->add($account);
    }

    #[Given('user :username with role :role of account :account exists')]
    public function userWithRoleOfAccountExists(string $username, string $role, string $account): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $user = UserMother::fromUsernameAndRoleAndAccount($username, Role::tryFrom($role), new AccountId($account));

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with id :id of account :account exists')]
    public function userWithIdOfAccountExists(string $username, string $id, string $account): void
    {
        $user = UserMother::from(
            userId: new UserId($id),
            username: $username,
            role: Role::User,
            accountId: new AccountId($account),
        );

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with role :role with id :id of account :account exists')]
    public function userWithRoleWithIdOfAccountExists(string $username, string $id, string $role, string $account): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $user = UserMother::from(
            userId: new UserId($id),
            username: $username,
            role: Role::tryFrom($role),
            accountId: new AccountId($account),
        );

        $this->getUserRepository()->add($user);
    }

    #[Given('user :username with recover token :token exists')]
    public function userWithRecoverTokenExists(string $username, string $token): void
    {
        $user = UserMother::fromUsernameAndRecoverToken($username, $token);

        $this->getUserRepository()->add($user);
    }

    #[Given('signature request with id :id and account :accountId exists')]
    public function signatureRequestWithIdAndAccountExists(string $id, string $accountId): void
    {
        $signatureRequest = SignatureRequestMother::fromIdAndAccountId($id, $accountId);

        $this->getSignatureRequestRepository()->add($signatureRequest);
    }

    #[Given('a readable temp pdf file exists at :path')]
    public function aReadableTempPdfFileExistsAt(string $path): void
    {
        file_put_contents($path, '%PDF-1.4 test content');
    }

    #[Given('signature request with id :id and account :accountId and token :plainValue exists')]
    public function signatureRequestWithTokenExists(string $id, string $accountId, string $plainValue): void
    {
        /** @var string $appSecret */
        $appSecret = $this->client->getContainer()->getParameter('app.signature_hmac_secret');

        $token = new Token(
            hash: Token::hashFor($plainValue, $appSecret),
            plainValue: $plainValue,
        );

        $signatureRequest = new SignatureRequest(id: new SignatureRequestId($id));
        $signatureRequest->register(
            token: $token,
            documentToSign: new DocumentReference(
                sha256: hash('sha256', 'test-content'),
                size: 100,
                filename: 'test.pdf',
                path: '/tmp/test.pdf',
            ),
            signer: new Signer(name: 'Test Signer', locale: Locale::Spanish, email: 'signer@example.com'),
            accountId: new AccountId($accountId),
        );

        $this->getSignatureRequestRepository()->add($signatureRequest);

        $this->setOtpVerifiedInSession($plainValue);
    }

    #[Given('signed signature request with id :id and account :accountId and token :plainValue exists')]
    public function signedSignatureRequestWithTokenExists(string $id, string $accountId, string $plainValue): void
    {
        /** @var string $appSecret */
        $appSecret = $this->client->getContainer()->getParameter('app.signature_hmac_secret');

        $token = new Token(
            hash: Token::hashFor($plainValue, $appSecret),
            plainValue: $plainValue,
        );

        $signatureRequest = new SignatureRequest(id: new SignatureRequestId($id));
        $signatureRequest->register(
            token: $token,
            documentToSign: new DocumentReference(
                sha256: hash('sha256', 'test-content'),
                size: 100,
                filename: 'test.pdf',
                path: '/tmp/test.pdf',
            ),
            signer: new Signer(name: 'Test Signer', locale: Locale::Spanish, email: 'signer@example.com'),
            accountId: new AccountId($accountId),
        );

        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: hash('sha256', 'original'),
            signedDocumentSha256: hash('sha256', 'signed'),
            driver: 'hmac',
            proofPayload: 'test-proof',
            signerIp: '127.0.0.1',
        );
        $signatureRequest->sign($proof, new DocumentReference(
            sha256: hash('sha256', 'signed'),
            size: 100,
            filename: 'signed.pdf',
            path: '/tmp/signed.pdf',
        ));

        $this->getSignatureRequestRepository()->add($signatureRequest);

        $this->setOtpVerifiedInSession($plainValue);
    }

    #[Given('signed signature request with id :id and account :accountId exists')]
    public function signedSignatureRequestWithIdAndAccountExists(string $id, string $accountId): void
    {
        $signatureRequest = SignatureRequestMother::fromIdAndAccountId($id, $accountId);

        $proof = SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: hash('sha256', 'original'),
            signedDocumentSha256: hash('sha256', 'signed'),
            driver: 'hmac',
            proofPayload: 'test-proof',
            signerIp: '127.0.0.1',
            signerUserAgent: 'TestAgent',
        );

        $documentSigned = new DocumentReference(
            sha256: hash('sha256', 'signed'),
            size: 100,
            filename: 'signed.pdf',
            path: '/tmp/signed.pdf',
        );

        $signatureRequest->sign($proof, $documentSigned);

        $this->getSignatureRequestRepository()->add($signatureRequest);
    }

    #[Given('signature request with id :id and account :accountId and token :plainValue and SMS channel exists')]
    public function signatureRequestWithSmsChannelExists(string $id, string $accountId, string $plainValue): void
    {
        /** @var string $appSecret */
        $appSecret = $this->client->getContainer()->getParameter('app.signature_hmac_secret');

        $token = new Token(
            hash: Token::hashFor($plainValue, $appSecret),
            plainValue: $plainValue,
        );

        $signatureRequest = new SignatureRequest(id: new SignatureRequestId($id));
        $signatureRequest->register(
            token: $token,
            documentToSign: new DocumentReference(
                sha256: hash('sha256', 'test-content'),
                size: 100,
                filename: 'test.pdf',
                path: '/tmp/test.pdf',
            ),
            signer: new Signer(
                name: 'SMS Signer',
                locale: Locale::Spanish,
                email: 'signer@example.com',
                phone: '+34600000000',
                notificationChannel: NotificationChannel::Sms,
            ),
            accountId: new AccountId($accountId),
        );

        $challenge = OtpChallenge::generate($appSecret);
        $this->lastOtpCode = $challenge->plainCode();
        $signatureRequest->sendOtp($challenge);

        $this->getSignatureRequestRepository()->add($signatureRequest);
    }

    #[When('I submit the OTP from the last SMS to :path')]
    public function iSubmitTheOtpFromTheLastSmsTo(string $path): void
    {
        Assert::assertNotEmpty($this->lastOtpCode, 'No se generó ningún OTP en el paso Given');

        $form = $this->client->getCrawler()->filter('form')->first()->form();
        $form['otp_code'] = $this->lastOtpCode;
        $this->client->submit($form);
    }

    #[When('I submit a wrong OTP to :path')]
    public function iSubmitAWrongOtpTo(string $path): void
    {
        $form = $this->client->getCrawler()->filter('form')->first()->form();
        $form['otp_code'] = '000000';
        $this->client->submit($form);
    }

    #[When('I resend the OTP to :path')]
    public function iResendTheOtpTo(string $path): void
    {
        $forms = $this->client->getCrawler()->filter('form');
        $resendForm = $forms->count() > 1 ? $forms->last()->form() : $forms->first()->form();
        $this->client->submit($resendForm);
    }

    #[When('I impersonate account :name')]
    public function iImpersonateAccount(string $name): void
    {
        $account = $this->getAccountRepository()->findByName($name);
        Assert::assertNotNull($account, sprintf('Account "%s" not found', $name));

        $this->client->request('GET', '/es/profile');
        $token = $this->extractImpersonationToken() ?? $this->generateImpersonationToken();

        $this->client->requestWithFiles('POST', '/es/admin/accounts/impersonate', [
            'accountId' => (string) $account->id(),
            '_token' => $token,
        ], []);
    }

    #[When('I exit account impersonation')]
    public function iExitAccountImpersonation(): void
    {
        $this->client->request('GET', '/es/profile');
        $token = $this->extractImpersonationToken() ?? $this->generateImpersonationToken();

        $this->client->requestWithFiles('POST', '/es/admin/accounts/impersonate/exit', [
            '_token' => $token,
        ], []);
    }

    private function extractImpersonationToken(): ?string
    {
        try {
            $wrapper = $this->client->getCrawler()->filter('[data-account-switch-token-value]');
        } catch (\Throwable) {
            return null;
        }

        if (0 === $wrapper->count()) {
            return null;
        }

        return $wrapper->first()->attr('data-account-switch-token-value');
    }

    private function generateImpersonationToken(): string
    {
        try {
            $csrfManager = $this->client->getContainer()->get('security.csrf.token_manager');

            Assert::assertInstanceOf(CsrfTokenManagerInterface::class, $csrfManager);

            return $csrfManager->getToken('account_impersonate')->getValue();
        } catch (\Throwable) {
            return '';
        }
    }

    #[Then('the recorded events of :username are impersonated by :impersonator')]
    public function theRecordedEventsOfAreImpersonatedBy(string $username, string $impersonator): void
    {
        $user = $this->getUserRepository()->findByUsername($username);
        $impersonatorUser = $this->getUserRepository()->findByUsername($impersonator);

        Assert::assertNotNull($user);
        Assert::assertNotNull($impersonatorUser);

        $events = $this->getRecordedEventRepository()->findByFilter(new RecordedEventFilter(
            userId: $user->id(),
            impersonatorId: $impersonatorUser->id(),
            limit: 0,
        ));

        Assert::assertNotEmpty($events, 'No se registró ningún evento suplantado');

        foreach ($events as $event) {
            Assert::assertTrue($event->isImpersonated());
            Assert::assertEquals($impersonatorUser->id(), $event->impersonatorId());
        }
    }

    #[Then('no recorded event is impersonated')]
    public function noRecordedEventIsImpersonated(): void
    {
        $events = $this->getRecordedEventRepository()->findByFilter(new RecordedEventFilter(limit: 0));

        foreach ($events as $event) {
            Assert::assertFalse($event->isImpersonated());
        }
    }

    private function setOtpVerifiedInSession(string $plainToken): void
    {
        /** @var string $appSecret */
        $appSecret = $this->client->getContainer()->getParameter('app.signature_hmac_secret');
        $tokenHash = Token::hashFor($plainToken, $appSecret);
        $signatureRequest = $this->getSignatureRequestRepository()->findByToken($tokenHash);

        if (null === $signatureRequest) {
            return;
        }

        $challenge = OtpChallenge::generate($appSecret);
        $signatureRequest->sendOtp($challenge);
        $signatureRequest->verifyOtp($challenge->plainCode(), $appSecret);
        $this->getSignatureRequestRepository()->add($signatureRequest);
    }
}
