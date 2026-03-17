<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

trait FilterTrait
{
    /**
     * @return list<mixed>
     */
    public function filter(Request $request, \Closure $createFilter, string $formClass): array
    {
        $session = $request->getSession();
        $route = $request->attributes->get('_route');

        $key = sprintf("%s-%s", $route, md5($formClass));

        $filter = $session->has($key) ? $session->get($key) : $createFilter();

        $form = $this->createForm($formClass, $filter);

        assert($form instanceof Form);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            if ($form->getClickedButton() && 'reset' === $form->getClickedButton()->getName()) {
                $filter = $createFilter();

                $form = $this->createForm($formClass, $filter);
            }

            $session->set($key, $filter);
        }

        return [$filter, $form];
    }
}
