<?php

declare(strict_types=1);

namespace App\Tests\Service\Crypto;

use App\Service\Crypto\Encryptor;
use App\Tests\Service\ServiceTestCase;

final class EncryptorTest extends ServiceTestCase
{
    public function testEncryptDecrypt(): void
    {
        $encryptor = self::getService(Encryptor::class);

        $encrypted = $encryptor->encrypt($original = 'hello world');
        self::assertNotSame('hello world', $encrypted);

        $decrypted = $encryptor->decrypt($encrypted);
        self::assertSame($original, $decrypted);
    }
}
