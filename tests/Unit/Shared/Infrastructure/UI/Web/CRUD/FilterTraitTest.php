<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\CRUD;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineEntityReattacher;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class FilterTraitTest extends TestCase
{
    public function testFilterKeyIncludesAccountId(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $accountId = AccountId::generate();
        $host = $this->buildHost(new StubProvider($accountId));

        $host->filter($this->buildRequest($session, 'admin_users'), fn() => 'initial', TextType::class);

        $key = $this->expectedKey('admin_users', (string) $accountId);

        $this->assertTrue($session->has($key));
        $this->assertSame('submitted', $session->get($key));
    }

    public function testDifferentAccountsProduceDifferentKeys(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $accountOne = AccountId::generate();
        $accountTwo = AccountId::generate();

        $this->buildHost(new StubProvider($accountOne))
            ->filter($this->buildRequest($session, 'admin_users'), fn() => 'initial', TextType::class);

        $this->buildHost(new StubProvider($accountTwo))
            ->filter($this->buildRequest($session, 'admin_users'), fn() => 'initial', TextType::class);

        $keyOne = $this->expectedKey('admin_users', (string) $accountOne);
        $keyTwo = $this->expectedKey('admin_users', (string) $accountTwo);

        $this->assertNotSame($keyOne, $keyTwo);
        $this->assertTrue($session->has($keyOne));
        $this->assertTrue($session->has($keyTwo));
    }

    public function testNullAccountUsesNoneAsKeySegment(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $host = $this->buildHost(new StubProvider(null));

        $host->filter($this->buildRequest($session, 'admin_users'), fn() => 'initial', TextType::class);

        $key = $this->expectedKey('admin_users', 'none');

        $this->assertTrue($session->has($key));
    }

    private function buildHost(CurrentAccountIdProvider $provider): FilterTraitHost
    {
        $host = new FilterTraitHost(
            Forms::createFormFactoryBuilder()
                ->addExtension(new HttpFoundationExtension())
                ->getFormFactory(),
        );
        $host->setDoctrineEntityReattacher(new DoctrineEntityReattacher($this->createMock(EntityManagerInterface::class)));
        $host->setCurrentAccountIdProvider($provider);

        return $host;
    }

    private function buildRequest(Session $session, string $route): Request
    {
        $request = Request::create('/' . $route, 'POST');
        $request->setSession($session);
        $request->attributes->set('_route', $route);
        $request->request->set('text', 'submitted');

        return $request;
    }

    private function expectedKey(string $route, string $accountId): string
    {
        return sprintf('%s-%s-%s', $route, md5(TextType::class), $accountId);
    }
}

final class FilterTraitHost
{
    use FilterTrait;

    public function __construct(private readonly FormFactoryInterface $formFactory) {}

    /**
     * @param class-string<FormTypeInterface<mixed>> $type
     * @param array<string, mixed>                   $options
     *
     * @return FormInterface<mixed>
     */
    public function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create($type, $data, $options);
    }
}

final class StubProvider implements CurrentAccountIdProvider
{
    public function __construct(private readonly ?AccountId $accountId) {}

    public function currentAccountId(): ?AccountId
    {
        return $this->accountId;
    }
}
