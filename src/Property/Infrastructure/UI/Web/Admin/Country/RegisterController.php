<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Country;

use App\Property\Application\Country\Register\RegisterCountryService;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Infrastructure\Symfony\Form\AdminRegisterCountryType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/countries/register', name: 'admin_countries_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterCountryService $registerCountry, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Action::Register->value, new Country(CountryId::generate()));

        $form = $this->createForm(AdminRegisterCountryType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCountry, $loggedUser): true {
                $registerRequest = $form->getData();

                $response = $registerCountry($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('País creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_countries');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/countries/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
