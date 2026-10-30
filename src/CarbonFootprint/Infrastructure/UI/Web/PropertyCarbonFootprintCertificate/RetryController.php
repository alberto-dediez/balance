<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate\GeneratePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprint-certificates/{certificateId}/retry', name: 'property_carbon_footprint_certificates_retry', methods: ['GET'])]
#[CsrfProtected('retry')]
class RetryController extends AbstractController
{
    use GetPropertyCarbonFootprintCertificateFromIdTrait;

    public function __invoke(
        string $certificateId,
        PropertyCarbonFootprintCertificateRepository $certificateRepository,
        LoggedUser $loggedUser,
    ): Response {
        $certificate = $this->getPropertyCarbonFootprintCertificateFromId($certificateId, $certificateRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $certificate);

        if ($certificate->status()->isFailed()) {
            CommandBusDispatcher::instance()->dispatch(
                new DeferredCommand(new GeneratePropertyCarbonFootprintCertificateRequest($certificate->id()), Priority::LOW),
                $loggedUser(),
            );

            $this->addFlash('success', new TranslatableMessage('Se está volviendo a generar el certificado de huella.'));
        }

        return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => (string) $certificate->propertyCarbonFootprintId()]);
    }
}
