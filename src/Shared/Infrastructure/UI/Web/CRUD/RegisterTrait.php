<?php

namespace App\Shared\Infrastructure\UI\Web\CRUD;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatableMessage;

trait RegisterTrait
{
    /**
     * @param FormInterface<mixed> $form
     */
    public function register(Request $request, FormInterface $form, \Closure $register): bool
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                return $register($form);
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado'));
            }
        }

        return false;
    }
}
