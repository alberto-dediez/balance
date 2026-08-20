<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\UI\Web\Admin\Account\ExitImpersonationController;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ExitImpersonationControllerTest extends TestCase
{
    public function testFlagOffThrowsNotFound(): void
    {
        $sut = $this->buildSut(false, $this->csrfValid());

        $this->expectException(NotFoundHttpException::class);

        $sut($this->buildRequest('valid'), $this->buildImpersonated(new InMemoryAccountRepository()), $this->buildLoggedUser(UserMother::adminUser()));
    }

    public function testInvalidCsrfReturns400(): void
    {
        $sut = $this->buildSut(true, $this->createMock(CsrfTokenManagerInterface::class));

        $response = $sut($this->buildRequest('invalid'), $this->buildImpersonated(new InMemoryAccountRepository()), $this->buildLoggedUser(UserMother::adminUser()));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testExitClearsTheImpersonationAndRedirectsToHome(): void
    {
        $account = AccountMother::fromNameAndId('AcmeAccount', 'acme-account-id');
        $repository = $this->buildRepository($account);

        $admin = UserMother::adminUser();

        $impersonated = $this->buildImpersonated($repository);
        $impersonated->impersonate($account->id(), $admin->id());

        $sut = $this->buildSut(true, $this->csrfValid());

        $response = $sut($this->buildRequest('valid'), $impersonated, $this->buildLoggedUser($admin));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($impersonated->isImpersonating());
        $this->assertSame(['ok' => true, 'redirect' => '/es/'], json_decode((string) $response->getContent(), true));
    }

    private function buildSut(bool $enabled, CsrfTokenManagerInterface $csrf): ExitImpersonationController
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/es/');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($router);

        $sut = new ExitImpersonationController($enabled, $csrf, new NullLogger());
        $sut->setContainer($container);

        return $sut;
    }

    private function csrfValid(): CsrfTokenManagerInterface
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        return $csrf;
    }

    private function buildRepository(Account ...$accounts): InMemoryAccountRepository
    {
        $repository = new InMemoryAccountRepository();

        foreach ($accounts as $account) {
            $repository->add($account);
        }

        return $repository;
    }

    private function buildImpersonated(InMemoryAccountRepository $repository): ImpersonatedAccount
    {
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new ImpersonatedAccount($requestStack, $repository, true);
    }

    private function buildLoggedUser(User $user): LoggedUser
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new SecurityUser($user));

        $loggedUser = new LoggedUser($security, new InMemoryUserRepository());
        $loggedUser->forceUser($user);

        return $loggedUser;
    }

    private function buildRequest(string $token): Request
    {
        $request = Request::create('/admin/accounts/impersonate/exit', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->request->set('_token', $token);

        return $request;
    }
}
