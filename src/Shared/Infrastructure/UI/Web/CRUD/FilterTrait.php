<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use Symfony\Component\HttpFoundation\Request;

trait FilterTrait
{
    public function filter(Request $request, \Closure $createFilter, string $formClass)
    {
        $session = $request->getSession();
        $route = $request->attributes->get('_route');

        $key = sprintf("%s-%s", $route, md5($formClass));

        if ($session->has($key)) {
            $filter = $session->get($key);
        } else {
            $filter = $createFilter();    
        }        

        $form = $this->createForm($formClass, $filter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            if ($form->getClickedButton() && 'reset' == $form->getClickedButton()->getName()) {
                $filter = $createFilter();

                $form = $this->createForm($formClass, $filter);
            }

            $session->set($key, $filter);      
        }

        return [$filter, $form];
    }
}