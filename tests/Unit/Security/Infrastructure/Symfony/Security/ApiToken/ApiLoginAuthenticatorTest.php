<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security\ApiToken;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Application\ApiToken\Authenticate\AuthenticateWithApiTokenService;
use App\Security\Application\ApiToken\MarkAsUsed\MarkApiTokenAsUsedService;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;
use App\Security\Infrastructure\Symfony\Security\ApiToken\ApiLoginAuthenticator;
use App\Security\Infrastructure\Symfony\Security\ApiToken\ApiTokenBadge;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenMother;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class ApiLoginAuthenticatorTest extends TestCase
{
    private const PASSWORD = 'the-password';

    private SecurityUser $user;

    private InMemoryApiTokenRepository $repository;

    protected function setUp(): void
    {
        $this->user = new SecurityUser(UserMother::adminUser());
        $this->repository = ApiTokenRepositoryMother::inMemory();
    }

    public function testValidPasswordDoesNotQueryTokensNorAddsAttribute(): void
    {
        $secret = ApiTokenSecret::generate();
        $apiToken = ApiTokenMother::withSecret($secret, $this->user->id());
        $this->repository->add($apiToken);

        $sut = $this->createSUT(self::PASSWORD);
        $passport = $sut->authenticate(new Request());

        $this->checkCredentials($passport);

        $token = $sut->createToken($passport, 'api_login');

        $this->assertFalse($token->hasAttribute(ApiTokenBadge::ATTRIBUTE));
        $this->assertNull($apiToken->lastUsedAt());
    }

    public function testValidApiTokenAddsAttribute(): void
    {
        $secret = ApiTokenSecret::generate();
        $apiToken = ApiTokenMother::withSecret($secret, $this->user->id());
        $this->repository->add($apiToken);

        $sut = $this->createSUT($secret->plainValue());
        $passport = $sut->authenticate(new Request());

        $this->checkCredentials($passport);

        $token = $sut->createToken($passport, 'api_login');

        $this->assertTrue($token->hasAttribute(ApiTokenBadge::ATTRIBUTE));
        $this->assertSame((string) $apiToken->id(), $token->getAttribute(ApiTokenBadge::ATTRIBUTE));
        $this->assertNull($apiToken->lastUsedAt());
    }

    public function testAuthenticationSuccessWithApiTokenMarksItAsUsed(): void
    {
        $secret = ApiTokenSecret::generate();
        $apiToken = ApiTokenMother::withSecret($secret, $this->user->id());
        $this->repository->add($apiToken);

        $sut = $this->createSUT($secret->plainValue());
        $passport = $sut->authenticate(new Request());

        $this->checkCredentials($passport);

        $token = $sut->createToken($passport, 'api_login');

        $sut->onAuthenticationSuccess(new Request(), $token, 'api_login');

        $this->assertNotNull($apiToken->lastUsedAt());
    }

    public function testAuthenticationSuccessWithPasswordDoesNotMarkTokensAsUsed(): void
    {
        $secret = ApiTokenSecret::generate();
        $apiToken = ApiTokenMother::withSecret($secret, $this->user->id());
        $this->repository->add($apiToken);

        $sut = $this->createSUT(self::PASSWORD);
        $passport = $sut->authenticate(new Request());

        $this->checkCredentials($passport);

        $token = $sut->createToken($passport, 'api_login');

        $sut->onAuthenticationSuccess(new Request(), $token, 'api_login');

        $this->assertNull($apiToken->lastUsedAt());
    }

    public function testInvalidPasswordAndTokenAreRejected(): void
    {
        $this->expectException(BadCredentialsException::class);

        $this->repository->add(ApiTokenMother::active($this->user->id()));

        $sut = $this->createSUT('hyd_wrong');

        $this->checkCredentials($sut->authenticate(new Request()));
    }

    public function testEmptySecretIsRejected(): void
    {
        $this->expectException(BadCredentialsException::class);

        $sut = $this->createSUT('');

        $this->checkCredentials($sut->authenticate(new Request()));
    }

    public function testPassportDoesNotCarryPasswordCredentials(): void
    {
        $passport = $this->createSUT(self::PASSWORD)->authenticate(new Request());

        $this->assertFalse($passport->hasBadge(PasswordCredentials::class));
        $this->assertTrue($passport->hasBadge(CustomCredentials::class));
        $this->assertTrue($passport->hasBadge(ApiTokenBadge::class));
    }

    private function checkCredentials(Passport $passport): void
    {
        $credentials = $passport->getBadge(CustomCredentials::class);

        $this->assertInstanceOf(CustomCredentials::class, $credentials);

        $credentials->executeCustomChecker($passport->getUser());
    }

    private function createSUT(#[\SensitiveParameter] string $secret): ApiLoginAuthenticator
    {
        $user = $this->user;

        $inner = new class ($user, $secret) implements InteractiveAuthenticatorInterface {
            public function __construct(private SecurityUser $user, private string $secret) {}

            public function supports(Request $request): ?bool
            {
                return true;
            }

            public function authenticate(Request $request): Passport
            {
                $user = $this->user;

                return new Passport(
                    new UserBadge($user->getUserIdentifier(), static fn(): SecurityUser => $user),
                    new PasswordCredentials($this->secret),
                );
            }

            public function createToken(Passport $passport, string $firewallName): TokenInterface
            {
                return new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
            }

            public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
            {
                return null;
            }

            public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
            {
                return null;
            }

            public function isInteractive(): bool
            {
                return true;
            }
        };

        $passwordHasher = new class (self::PASSWORD) implements UserPasswordHasherInterface {
            public function __construct(private string $validPassword) {}

            public function hashPassword(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): string
            {
                return $plainPassword;
            }

            public function isPasswordValid(PasswordAuthenticatedUserInterface $user, #[\SensitiveParameter] string $plainPassword): bool
            {
                return $plainPassword === $this->validPassword;
            }

            public function needsRehash(PasswordAuthenticatedUserInterface $user): bool
            {
                return false;
            }
        };

        return new ApiLoginAuthenticator(
            $inner,
            $passwordHasher,
            new AuthenticateWithApiTokenService($this->repository, new ApiTokenPolicy(AccountRepositoryMother::inMemory())),
            new MarkApiTokenAsUsedService($this->repository),
            $this->createMock(JWTTokenManagerInterface::class),
            $this->createMock(AuthenticationSuccessHandler::class),
        );
    }
}
