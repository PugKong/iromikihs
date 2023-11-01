<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Client\RequestStub;

use App\Shikimori\Client\Config;
use App\Shikimori\Client\FormRequest;

readonly class FormRequestStub extends RequestStub implements FormRequest
{
    public function form(Config $config): array
    {
        return ['foo' => 'bar'];
    }
}
