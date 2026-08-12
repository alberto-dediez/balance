<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Symfony\Form\Type;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
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

    public function testItExposesTheRequiredVisibilityToTheWidget(): void
    {
        $view = $this->createForm(visibility: MediaVisibility::System)->createView();

        $attributes = $view->children['picture']->vars['attr'];

        $this->assertEquals('System', $attributes['data-media-picker-visibility-value']);
    }

    /**
     * The token proves the application really asked for that visibility, so it cannot
     * be reused to upload a file reaching a wider audience.
     */
    public function testTheTokenIsBoundToTheRequiredVisibility(): void
    {
        $withoutVisibility = $this->createForm()->createView()->children['picture']->vars['attr'];
        $shared = $this->createForm(visibility: MediaVisibility::System)->createView()->children['picture']->vars['attr'];

        $this->assertNotEquals(
            $withoutVisibility['data-media-picker-token-value'],
            $shared['data-media-picker-token-value'],
        );

        $this->assertEquals(MediaPickerType::CSRF_TOKEN_ID, MediaPickerType::csrfTokenId(null));
        $this->assertEquals(MediaPickerType::CSRF_TOKEN_ID, MediaPickerType::csrfTokenId(MediaVisibility::Account));
        $this->assertNotEquals(MediaPickerType::CSRF_TOKEN_ID, MediaPickerType::csrfTokenId(MediaVisibility::System));
    }

    /**
     * A file only works where it is used once it reaches the audience the field
     * requires, so picking it widens its visibility.
     */
    public function testThePickedMediaReachesTheRequiredVisibility(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm(visibility: MediaVisibility::System);

        $form->submit(['picture' => (string) $media->id()]);

        $this->assertStringNotContainsString('No puedes usar ese archivo', (string) $form->getErrors(true));

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::System, $mediaPersisted->visibility());
    }

    public function testAFileAlreadyReachingAWiderAudienceIsLeftUntouched(): void
    {
        $media = MediaMother::fromNameAndVisibility('terms.png', MediaVisibility::Public);

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm(visibility: MediaVisibility::System);

        $form->submit(['picture' => (string) $media->id()]);

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::Public, $mediaPersisted->visibility());
    }

    public function testAFieldWithoutARequiredVisibilityLeavesThePickedMediaAsItIs(): void
    {
        $media = MediaMother::uploadedMedia();

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm();

        $form->submit(['picture' => (string) $media->id()]);

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::Account, $mediaPersisted->visibility());
    }

    public function testAFileOutOfReachOfTheUserIsRejected(): void
    {
        $media = MediaMother::fromNameAndAccount('foreign.png', AccountId::generate());

        $this->getRepository()->add($media);

        $this->logUser();

        $form = $this->createForm(visibility: MediaVisibility::System);

        $form->submit(['picture' => (string) $media->id()]);

        $this->assertStringContainsString('No puedes usar ese archivo', (string) $form->getErrors(true));

        $mediaPersisted = $this->getRepository()->find($media->id());

        $this->assertNotNull($mediaPersisted);
        $this->assertEquals(MediaVisibility::Account, $mediaPersisted->visibility());
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
    private function createForm(?MediaId $mediaId = null, array $types = [], ?MediaVisibility $visibility = null): FormInterface
    {
        $factory = self::getContainer()->get('form.factory');

        return $factory
            ->createBuilder()
            ->add('picture', MediaPickerType::class, [
                'types' => $types,
                'visibility' => $visibility,
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

    private function logUser(): void
    {
        self::getContainer()->get(LoggedUser::class)->forceUser(UserMother::adminUser());
    }

    private function getRepository(): MediaRepository
    {
        return self::getContainer()->get(MediaRepository::class);
    }
}
