<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Security;

use App\Security\Application\EmailLog\Purge\PurgeEmailLogsRequest;
use App\Security\Application\EmailLog\Purge\PurgeEmailLogsService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\EmailLog\EmailLogFilter;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
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
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Sso\InMemory\InMemorySsoProvider;
use App\Security\Infrastructure\Sso\SsoProviderRegistry;
use App\Security\Infrastructure\Symfony\Event\UserLogged;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTwoFactorPolicy;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Symfony\Event\EmailListener;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogMother;
use App\Tests\Unit\Security\Domain\Model\RecordedEvent\RecordedEventMother;
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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Mime\Email;
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

        foreach ($this->getSsoProviderRegistry()->all() as $provider) {
            if ($provider instanceof InMemorySsoProvider) {
                $provider->reset();
            }
        }

        $this->getTwoFactorPolicy()->reset();
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

    private function getEmailLogRepository(): EmailLogRepository
    {
        return $this->client->getContainer()->get(EmailLogRepository::class);
    }

    private function getSsoProviderRegistry(): SsoProviderRegistry
    {
        $registry = $this->client->getContainer()->get(SsoProviderRegistry::class);

        Assert::assertInstanceOf(SsoProviderRegistry::class, $registry);

        return $registry;
    }

    private function getInMemorySsoProvider(string $key): InMemorySsoProvider
    {
        $provider = $this->getSsoProviderRegistry()->get($key);

        Assert::assertInstanceOf(InMemorySsoProvider::class, $provider);

        return $provider;
    }

    private function getTwoFactorPolicy(): InMemoryTwoFactorPolicy
    {
        $policy = $this->client->getContainer()->get(InMemoryTwoFactorPolicy::class);

        Assert::assertInstanceOf(InMemoryTwoFactorPolicy::class, $policy);

        return $policy;
    }

    private function currentCrawler(): Crawler
    {
        return new Crawler((string) $this->client->getResponse()->getContent(), $this->client->getRequest()->getUri());
    }

    private function twoFactorForm(string $action): Form
    {
        $button = $this->currentCrawler()->filterXPath(sprintf('//form[.//input[@name="action" and @value="%s"]]//button', $action));

        Assert::assertGreaterThan(0, $button->count(), sprintf('No se encontró el formulario "%s" del segundo factor', $action));

        return $button->first()->form();
    }

    private function submitTwoFactorCode(string $code): void
    {
        $form = $this->twoFactorForm('verify');
        $form['code'] = $code;

        $this->client->submit($form);
    }

    #[Given('two factor authentication is required')]
    public function twoFactorAuthenticationIsRequired(): void
    {
        $this->getTwoFactorPolicy()->require();
    }

    #[Given('two factor authentication is optional')]
    public function twoFactorAuthenticationIsOptional(): void
    {
        $this->getTwoFactorPolicy()->makeOptional();
    }

    #[Given('two factor authentication for SSO is enabled')]
    public function twoFactorAuthenticationForSsoIsEnabled(): void
    {
        $this->getTwoFactorPolicy()->enableSso();
    }

    #[Given('user :username with two factor method :method exists')]
    public function userWithTwoFactorMethodExists(string $username, string $method): void
    {
        $this->getUserRepository()->add(UserMother::withTwoFactorMethod($username, TwoFactorMethod::from($method), InMemoryTotpAuthenticator::SECRET));
    }

    #[Given('user :username with an unreadable authenticator secret exists')]
    public function userWithAnUnreadableAuthenticatorSecretExists(string $username): void
    {
        $this->getUserRepository()->add(UserMother::withTwoFactorMethod($username, TwoFactorMethod::Totp, InMemoryTotpAuthenticator::UNREADABLE_SECRET));
    }

    #[Given('user :username with id :id and two factor method :method exists')]
    public function userWithIdAndTwoFactorMethodExists(string $username, string $id, string $method): void
    {
        $this->addUserWithTwoFactorMethod($username, new UserId($id), null, TwoFactorMethod::from($method));
    }

    #[Given('user :username with id :id of account :account with two factor method :method exists')]
    public function userWithIdOfAccountWithTwoFactorMethodExists(string $username, string $id, string $account, string $method): void
    {
        $this->addUserWithTwoFactorMethod($username, new UserId($id), new AccountId($account), TwoFactorMethod::from($method));
    }

    private function addUserWithTwoFactorMethod(string $username, UserId $userId, ?AccountId $accountId, TwoFactorMethod $method): void
    {
        $user = UserMother::from(
            userId: $userId,
            username: $username,
            accountId: $accountId,
            twoFactorMethod: $method,
            totpSecret: TwoFactorMethod::Totp === $method ? InMemoryTotpAuthenticator::SECRET : null,
        );

        $this->getUserRepository()->add($user);
    }

    #[When('I sign in with :username and :password')]
    public function iSignInWith(string $username, string $password, bool $rememberMe = false): void
    {
        $this->client->request('GET', '/es/login');

        $form = $this->currentCrawler()->filter('input[name="_csrf_token"]')->closest('form')?->form();

        Assert::assertInstanceOf(Form::class, $form);

        $form['_username'] = $username;
        $form['_password'] = $password;

        if ($rememberMe) {
            $form['_remember_me'] = 'on';
        }

        $this->client->submit($form);
    }

    #[When('I sign in with :username and :password remembering me')]
    public function iSignInWithRememberingMe(string $username, string $password): void
    {
        $this->iSignInWith($username, $password, true);
    }

    #[When('my session expires')]
    public function mySessionExpires(): void
    {
        $this->client->clearCookiesExcept('REMEMBERME');
    }

    #[When('I submit the two factor code from the last email')]
    public function iSubmitTheTwoFactorCodeFromTheLastEmail(): void
    {
        $emailListener = $this->client->getContainer()->get(EmailListener::class);

        Assert::assertInstanceOf(EmailListener::class, $emailListener);

        $messages = $emailListener->getMessages();

        Assert::assertNotEmpty($messages, 'No se ha enviado ningún email');

        $lastMessage = $messages[count($messages) - 1];

        Assert::assertInstanceOf(Email::class, $lastMessage);

        $body = (string) ($lastMessage->getHtmlBody() ?? $lastMessage->getTextBody());

        Assert::assertMatchesRegularExpression('/(?<![#\w])(\d{6})(?!\w)/', $body, 'El email no contiene un código de 6 dígitos');

        preg_match('/(?<![#\w])(\d{6})(?!\w)/', $body, $matches);

        $this->submitTwoFactorCode($matches[1]);
    }

    #[When('I submit the valid authenticator code')]
    public function iSubmitTheValidAuthenticatorCode(): void
    {
        $this->submitTwoFactorCode(InMemoryTotpAuthenticator::VALID_CODE);
    }

    #[When('I submit a wrong two factor code')]
    public function iSubmitAWrongTwoFactorCode(): void
    {
        $this->submitTwoFactorCode('000000');
    }

    #[When('I submit a two factor code with an invalid csrf token')]
    public function iSubmitATwoFactorCodeWithAnInvalidCsrfToken(): void
    {
        $form = $this->twoFactorForm('verify');
        $form['code'] = '000000';
        $form['_token'] = 'invalid';

        $this->client->submit($form);
    }

    #[When('I resend the two factor code')]
    public function iResendTheTwoFactorCode(): void
    {
        $this->client->submit($this->twoFactorForm('resend'));
    }

    #[Then('going to :path redirects to :target')]
    public function goingToRedirectsTo(string $path, string $target): void
    {
        $this->client->catchExceptions(true);

        try {
            $this->client->request('GET', $path);
        } finally {
            $this->client->catchExceptions(false);
        }

        $response = $this->client->getResponse();

        Assert::assertTrue($response->isRedirect(), sprintf('La respuesta a "%s" no es una redirección (%d)', $path, $response->getStatusCode()));
        Assert::assertEquals($target, parse_url((string) $response->headers->get('Location'), PHP_URL_PATH));
    }

    #[Then('user :username has two factor method :method')]
    public function userHasTwoFactorMethod(string $username, string $method): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user);
        Assert::assertEquals(TwoFactorMethod::from($method), $user->twoFactorMethod());
    }

    #[Then('user :username has no two factor method')]
    public function userHasNoTwoFactorMethod(string $username): void
    {
        $user = $this->getUserRepository()->findByUsername($username);

        Assert::assertNotNull($user);
        Assert::assertNull($user->twoFactorMethod());
    }

    #[Then('the response has a remember me cookie')]
    public function theResponseHasARememberMeCookie(): void
    {
        Assert::assertTrue($this->responseHasRememberMeCookie(), 'La respuesta no emite la cookie REMEMBERME');
    }

    #[Then('the response has no remember me cookie')]
    public function theResponseHasNoRememberMeCookie(): void
    {
        Assert::assertFalse($this->responseHasRememberMeCookie(), 'La respuesta emite la cookie REMEMBERME');
    }

    private function responseHasRememberMeCookie(): bool
    {
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ('REMEMBERME' === $cookie->getName() && !$cookie->isCleared() && '' !== (string) $cookie->getValue()) {
                return true;
            }
        }

        return false;
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

    #[Given('user with id :id has logged in')]
    public function userWithIdHasLoggedIn(string $id): void
    {
        $this->getRecordedEventRepository()->add(RecordedEventMother::from(
            userId: new UserId($id),
            event: new UserLogged(new UserId($id), 'main'),
        ));
    }

    #[Given('user :username with role :role with id :id exists')]
    public function userWithRoleWithIdExists(string $username, string $role, string $id): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $user = UserMother::fromUsernameAndRoleAndId($username, Role::tryFrom($role), new UserId($id));

        $this->getUserRepository()->add($user);
    }

    #[Given('account :name with id :id exists')]
    public function accountWithIdExists(string $username, string $id): void
    {
        $account = AccountMother::fromNameAndId($username, $id);

        $this->getAccountRepository()->add($account);
    }

    #[Given('inactive account :name with id :id exists')]
    public function inactiveAccountWithIdExists(string $name, string $id): void
    {
        $account = AccountMother::fromNameAndId($name, $id);
        $account->changeStatus(false);

        $this->getAccountRepository()->add($account);
    }

    #[Given('the SSO provider :key identifies the user as :email')]
    public function theSsoProviderIdentifiesTheUserAs(string $key, string $email): void
    {
        $this->getInMemorySsoProvider($key)->identifyAs($email);
    }

    #[Given('the page :path was requested before signing in')]
    public function thePageWasRequestedBeforeSigningIn(string $path): void
    {
        $this->client->setSessionValue('_security.main.target_path', $path);
    }

    #[Given('the SSO provider :key fails')]
    public function theSsoProviderFails(string $key): void
    {
        $this->getInMemorySsoProvider($key)->fail();
    }

    #[Given('the SSO provider :key is disabled')]
    public function theSsoProviderIsDisabled(string $key): void
    {
        $this->getInMemorySsoProvider($key)->disable();
    }

    #[Given('user :username with role :role of account :account exists')]
    public function userWithRoleOfAccountExists(string $username, string $role, string $account): void
    {
        assertNotEmpty(Role::tryFrom($role));

        $accountId = new AccountId($account);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            $username,
            Role::tryFrom($role),
            $accountId,
            $this->getAccountRepository()->find($accountId),
        );

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

    #[Given('email log with id :id to :email exists')]
    public function emailLogWithIdToExists(string $id, string $email): void
    {
        $this->getEmailLogRepository()->add(EmailLogMother::fromId($id, $email));
    }

    #[Given('email log with id :id to :email exists with status :status')]
    public function emailLogWithIdToExistsWithStatus(string $id, string $email, string $status): void
    {
        $emailLogStatus = EmailLogStatus::tryFrom($status);

        Assert::assertInstanceOf(EmailLogStatus::class, $emailLogStatus);

        $now = new \DateTimeImmutable('NOW');

        $emailLog = EmailLogStatus::Failed === $emailLogStatus
            ? EmailLogMother::from(id: new EmailLogId($id), recipientEmail: $email, providerMessageId: null, status: EmailLogStatus::Failed, statusReason: 'transport down')
            : EmailLogMother::fromId($id, $email);

        match ($emailLogStatus) {
            EmailLogStatus::Delivered => $emailLog->markAsDelivered($now),
            EmailLogStatus::Deferred => $emailLog->markAsDeferred('deferred', $now),
            EmailLogStatus::Bounced => $emailLog->markAsBounced('bounced', $now),
            EmailLogStatus::Dropped => $emailLog->markAsDropped('dropped', $now),
            EmailLogStatus::Sent, EmailLogStatus::Failed => null,
        };

        $this->getEmailLogRepository()->add($emailLog);
    }

    #[Given('email log with id :id to :email sent :days days ago exists')]
    public function emailLogWithIdToSentDaysAgoExists(string $id, string $email, int $days): void
    {
        $this->getEmailLogRepository()->add(EmailLogMother::from(
            id: new EmailLogId($id),
            recipientEmail: $email,
            sentAt: new \DateTimeImmutable(sprintf('-%d days', $days)),
        ));
    }

    #[When('email logs are purged')]
    public function emailLogsArePurged(): void
    {
        $purgeEmailLogs = $this->client->getContainer()->get(PurgeEmailLogsService::class);

        $purgeEmailLogs(new PurgeEmailLogsRequest(new \DateTimeImmutable('NOW')), UserMother::systemUser());
    }

    #[Then('email log :id has status :status')]
    public function emailLogHasStatus(string $id, string $status): void
    {
        $emailLog = $this->getEmailLogRepository()->find(new EmailLogId($id));

        Assert::assertNotNull($emailLog, sprintf('No existe el email log "%s"', $id));
        Assert::assertSame($status, $emailLog->status()->value);
    }

    #[Then('email log :id has been opened :count times')]
    public function emailLogHasBeenOpenedTimes(string $id, int $count): void
    {
        $emailLog = $this->getEmailLogRepository()->find(new EmailLogId($id));

        Assert::assertNotNull($emailLog, sprintf('No existe el email log "%s"', $id));
        Assert::assertSame($count, $emailLog->openCount());
    }

    #[Then('there are :count email logs to :email')]
    #[Then('there is :count email log to :email')]
    public function thereAreEmailLogsTo(int $count, string $email): void
    {
        $emailLogs = $this->getEmailLogRepository()->findByFilter(new EmailLogFilter(recipient: $email, limit: 0));

        Assert::assertCount($count, $emailLogs);
    }

    #[Then('there is :count email log to :email with status :status')]
    public function thereIsEmailLogToWithStatus(int $count, string $email, string $status): void
    {
        $emailLogStatus = EmailLogStatus::tryFrom($status);

        Assert::assertInstanceOf(EmailLogStatus::class, $emailLogStatus);

        $emailLogs = $this->getEmailLogRepository()->findByFilter(new EmailLogFilter(recipient: $email, status: $emailLogStatus, limit: 0));

        Assert::assertCount($count, $emailLogs);
    }

    #[Then('email log :id does not exist')]
    public function emailLogDoesNotExist(string $id): void
    {
        Assert::assertNull($this->getEmailLogRepository()->find(new EmailLogId($id)));
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
