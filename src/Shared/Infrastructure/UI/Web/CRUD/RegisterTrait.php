<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;

trait RegisterTrait
{
    public function register(Request $request, $form, \Closure $register)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                return $register($form);
            } catch (\Exception $e) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado'));
            }
        }

        return false;
    }
}