<?php

declare(strict_types=1);

namespace App\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SimpleForm
{
    public const CSRF_TOKEN_ID = 'simple_form';
    public const CSRF_TOKEN_FIELD = '_token';

    private string $action;

    public function mount(string $action): void
    {
        $this->action = $action;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
