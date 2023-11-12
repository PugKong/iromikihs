<?php

declare(strict_types=1);

namespace App\Tests\Twig\Component;

use App\Twig\Component\SimpleForm;

final class SimpleFormTest extends ComponentTestCase
{
    private const COMPONENT_NAME = 'SimpleForm';
    private const CSRF_TOKEN_VALUE = '123';

    private CsrfTokenManagerSpy $csrfTokenManagerSpy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csrfTokenManagerSpy = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => self::CSRF_TOKEN_VALUE]);
        $this->csrfTokenManagerSpy->register(self::getContainer());
    }

    public function testComponentMount(): void
    {
        $component = $this->mountTwigComponent(self::COMPONENT_NAME, ['action' => $action = '/example']);

        self::assertInstanceOf(SimpleForm::class, $component);
        self::assertSame($action, $component->getAction());
    }

    public function testComponentRenders(): void
    {
        $rendered = $this->renderTwigComponent(
            self::COMPONENT_NAME,
            [
                'action' => $action = '/the/action',
                'class' => $classes = 'btn btn-sm',
            ],
            $content = 'Content',
        );

        $form = $rendered->crawler()->filter('form.btn');
        self::assertSame($action, $form->attr('action'));
        self::assertSame($classes, $form->attr('class'));
        self::assertSame($content, $form->text());
        self::assertSame(self::CSRF_TOKEN_VALUE, $form->filter('input[type="hidden"]')->attr('value'));

        $this->csrfTokenManagerSpy->assertCalls(1);
        $this->csrfTokenManagerSpy->assertHasCall('getToken', SimpleForm::CSRF_TOKEN_ID);
    }
}
