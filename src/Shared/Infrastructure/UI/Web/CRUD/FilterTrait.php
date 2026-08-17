<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineEntityReattacher;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

trait FilterTrait
{
    private readonly DoctrineEntityReattacher $doctrineEntityReattacher;

    private ?CurrentAccountIdProvider $currentAccountIdProvider = null;

    #[Required]
    public function setDoctrineEntityReattacher(DoctrineEntityReattacher $doctrineEntityReattacher): void
    {
        $this->doctrineEntityReattacher = $doctrineEntityReattacher;
    }

    #[Required]
    public function setCurrentAccountIdProvider(CurrentAccountIdProvider $currentAccountIdProvider): void
    {
        $this->currentAccountIdProvider = $currentAccountIdProvider;
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $formClass
     *
     * @return list<mixed>
     */
    public function filter(Request $request, \Closure $createFilter, string $formClass): array
    {
        $session = $request->getSession();
        $route = $request->attributes->get('_route');

        $accountId = $this->currentAccountIdProvider instanceof CurrentAccountIdProvider
            ? ($this->currentAccountIdProvider->currentAccountId() ?? 'none')
            : 'none';

        $key = sprintf('%s-%s-%s', $route, md5($formClass), (string) $accountId);

        $filter = $session->has($key) ? $session->get($key) : null;

        $filter = null !== $filter ? $this->doctrineEntityReattacher->reattach($filter) : $createFilter();

        $form = $this->createForm($formClass, $filter);

        assert($form instanceof Form);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            $clickedButton = $form->getClickedButton();

            if ($clickedButton instanceof FormInterface && 'reset' === $clickedButton->getName()) {
                $filter = $createFilter();

                $form = $this->createForm($formClass, $filter);
            }

            $session->set($key, $filter);
        }

        return [$filter, $form];
    }
}
