<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Symfony\Form\Type;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\SameOriginCsrfTokenManager;

class MediaPickerTypeTest extends KernelTestCase
{
    /**
     * The picker issues a CSRF token, so a request with a session is needed.
     */
    protected function setUp(): void
    {
        $requestStack = self::getContainer()->get(RequestStack::class);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack->push($request);
    }

    public function testItExposesTheSelectedMedia(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $view = $this->createForm($media->id())->createView();

        $selected = $view->children['picture']->vars['selected'];

        $this->assertCount(1, $selected);
        $this->assertEquals((string) $media->id(), $selected[0]['id']);
        $this->assertTrue($selected[0]['isImage']);
        $this->assertStringContainsString((string) $media->id(), $selected[0]['url']);
    }

    public function testItExposesTheAllowedTypesToTheWidget(): void
    {
        $view = $this->createForm(null, [MediaType::Image])->createView();

        $attributes = $view->children['picture']->vars['attr'];

        $this->assertStringContainsString('media-picker', $attributes['data-controller']);
        $this->assertEquals('Image', $attributes['data-media-picker-types-value']);
        $this->assertNotEmpty($attributes['data-media-picker-search-path-value']);
        $this->assertNotEmpty($attributes['data-media-picker-upload-path-value']);
        $this->assertNotEmpty($attributes['data-media-picker-token-value']);
    }

    /**
     * The picker uploads through fetch(), so it cannot use one of the stateless
     * token ids of the application: those resolve to the CSRF cookie name and are
     * only validated together with the double-submit cookie that the
     * csrf-protection controller writes when a form is submitted.
     */
    public function testItIssuesARealSecretToken(): void
    {
        $attributes = $this->createForm()->createView()->children['picture']->vars['attr'];

        $token = $attributes['data-media-picker-token-value'];

        $this->assertNotEquals('csrf-token', $token);
        $this->assertGreaterThanOrEqual(SameOriginCsrfTokenManager::TOKEN_MIN_LENGTH, strlen($token));
    }

    public function testItRendersTheWidget(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $html = $this->renderWidget($this->createForm($media->id())->createView());

        $this->assertStringContainsString('data-media-picker-target="selection"', $html);
        $this->assertStringContainsString('data-media-picker-target="modal"', $html);
        $this->assertStringContainsString(sprintf('value="%s"', (string) $media->id()), $html);
    }

    public function testTheSubmittedValueBecomesAMediaId(): void
    {
        $mediaId = MediaId::generate();

        $form = $this->createForm();

        $form->submit(['picture' => (string) $mediaId]);

        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(MediaId::class, $form->get('picture')->getData());
    }

    /**
     * @param list<MediaType> $types
     *
     * @return FormInterface<mixed>
     */
    private function createForm(?MediaId $mediaId = null, array $types = []): FormInterface
    {
        $factory = self::getContainer()->get('form.factory');

        return $factory
            ->createBuilder()
            ->add('picture', MediaPickerType::class, [
                'types' => $types,
                'data' => $mediaId,
            ])
            ->getForm()
        ;
    }

    private function renderWidget(FormView $view): string
    {
        $twig = self::getContainer()->get('twig');

        return $twig
            ->createTemplate('{{ form_widget(form.picture) }}')
            ->render(['form' => $view])
        ;
    }

    private function getRepository(): MediaRepository
    {
        return self::getContainer()->get(MediaRepository::class);
    }
}
