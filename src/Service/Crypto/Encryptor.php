<?php

declare(strict_types=1);

namespace App\Service\Crypto;

use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function strlen;

final readonly class Encryptor
{
    private string $key;

    public function __construct(
        #[Autowire(env: 'APP_SECRET')]
        string $key,
    ) {
        $this->key = $key;
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->getKey();

        return Crypto::encrypt(new HiddenString($plaintext), $key);
    }

    public function decrypt(string $ciphertext): string
    {
        $key = $this->getKey();

        return Crypto::decrypt($ciphertext, $key)->getString();
    }

    private function getKey(): EncryptionKey
    {
        $key = $this->key;
        if (strlen($key) < 32) {
            $repeats = 32 / strlen($key);
            $repeats = (int) $repeats;
            $key = str_repeat($key, $repeats + 1);
        }

        if (strlen($key) > 32) {
            $key = substr($key, 0, 32);
        }

        return new EncryptionKey(new HiddenString($key));
    }
}
