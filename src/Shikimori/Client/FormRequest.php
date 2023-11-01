<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

interface FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function form(Config $config): array;
}
