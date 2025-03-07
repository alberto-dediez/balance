<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use Symfony\Component\HttpFoundation\Request;

trait FilterTrait
{
    public function filter(Request $request, \Closure $createFilter, string $formClass)
    {
        $filter = $createFilter();

        $form = $this->createForm($formClass, $filter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            if ($form->getClickedButton() && 'reset' == $form->getClickedButton()->getName()) {
                $filter = $createFilter();

                $form = $this->createForm($formClass, $filter);
            }
        }

        return [$filter, $form];
    }
}